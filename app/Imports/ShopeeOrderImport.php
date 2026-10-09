<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductMarketplace;
use App\Services\Inventory\InventoryService;
use App\Traits\MapsImportErrors;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;

class ShopeeOrderImport implements ToCollection, WithChunkReading, WithEvents, WithHeadingRow
{
    use MapsImportErrors;
    use RemembersRowNumber;

    protected $batch;

    public $total_rows = 0;

    public $success_rows = 0;

    public $duplicate_rows = 0;

    public $updated_rows = 0;

    public $error_rows = 0;

    public function __construct(ImportBatch $batch)
    {
        $this->batch = $batch;
    }

    public function collection(Collection $rows): void
    {
        $accountId = $this->batch->marketplace_account_id;

        // 1. Extract Keys for Eager Loading
        $orderIds = $rows->pluck('no_pesanan')->filter()->map(fn ($id) => (string) $id)->unique()->toArray();
        $usernames = $rows->pluck('username_pembeli')->filter(fn ($val) => ! empty($val) && $val !== '-')->unique()->toArray();

        $skus = collect([]);
        foreach ($rows as $row) {
            $refSku = ! empty($row['nomor_referensi_sku']) && $row['nomor_referensi_sku'] !== '-' ? $row['nomor_referensi_sku'] : null;
            $parentSku = ! empty($row['sku_induk']) && $row['sku_induk'] !== '-' ? $row['sku_induk'] : null;
            $fallbackSku = 'SKU-'.substr(md5($row['nama_produk'] ?? 'unknown'), 0, 8);
            $skus->push($refSku ?? $parentSku ?? $fallbackSku);
        }
        $skus = $skus->unique()->toArray();

        // 2. Eager Load to Memory
        $existingOrders = Order::where('marketplace_account_id', $accountId)
            ->whereIn('external_order_id', $orderIds)
            ->get()->keyBy('external_order_id');

        $customers = Customer::where('marketplace_account_id', $accountId)
            ->whereIn('username', $usernames)
            ->get()->keyBy('username');

        $productMarketplaces = ProductMarketplace::where('marketplace_account_id', $accountId)
            ->whereIn('marketplace_sku', $skus)
            ->with('product')
            ->get()->keyBy('marketplace_sku');

        $masterProducts = Product::whereIn('sku', $skus)->get()->keyBy('sku');

        DB::beginTransaction();
        try {
            $ordersToUpsert = [];
            $validRows = [];
            $orderChangeTracker = []; // Tracks 'new', 'changed', 'unchanged' for accurate counting

            // Loop 1: Resolve Customers and Prepare Orders Upsert
            foreach ($rows as $row) {
                $orderId = (string) ($row['no_pesanan'] ?? '');
                if (! $orderId || strtolower($orderId) === 'no. pesanan' || strtolower($orderId) === 'no_pesanan') {
                    continue;
                }

                $validRows[] = $row; // Keep valid rows for Loop 2

                // Process Customer (Inline create if missing)
                $customerUsername = $row['username_pembeli'] ?? ($row['username'] ?? null);
                $customerId = null;
                if (! empty($customerUsername) && $customerUsername != '-') {
                    if (! isset($customers[$customerUsername])) {
                        $customerPhone = $row['no_telepon'] ?? null;
                        if ($customerPhone && str_starts_with($customerPhone, "'")) {
                            $customerPhone = substr($customerPhone, 1);
                        }

                        $newCustomer = Customer::firstOrCreate([
                            'marketplace_account_id' => $accountId,
                            'username' => $customerUsername,
                        ], [
                            'customer_code' => 'CUST-'.strtoupper(Str::random(6)),
                            'customer_name' => $row['nama_penerima'] ?? null,
                            'phone' => $customerPhone,
                            'address' => $row['alamat_pengiriman'] ?? null,
                            'city' => $row['kotakabupaten'] ?? null,
                            'province' => $row['provinsi'] ?? null,
                        ]);
                        $customers[$customerUsername] = $newCustomer;
                    }
                    $customerId = $customers[$customerUsername]->id;
                }

                // Prepare Order Upsert Data
                $existingOrder = $existingOrders->get($orderId);

                // We use the LAST row's data in the chunk for order-level fields
                $updateData = $ordersToUpsert[$orderId] ?? [];

                $updateData['marketplace_account_id'] = $accountId;
                $updateData['external_order_id'] = $orderId;
                if ($customerId) {
                    $updateData['customer_id'] = $customerId;
                } else {
                    $updateData['customer_id'] = $updateData['customer_id'] ?? ($existingOrder->customer_id ?? null);
                }

                if (empty($updateData['customer_id'])) {
                    \Log::error("Customer ID became null for order {$orderId}. CustomerUsername: {$customerUsername}, HasExisting: ".($existingOrders->has($orderId) ? 'Yes' : 'No'));
                    $fallback = Customer::firstOrCreate([
                        'marketplace_account_id' => $accountId,
                        'username' => 'unknown_fallback',
                    ], [
                        'customer_code' => 'CUST-'.strtoupper(Str::random(6)),
                        'customer_name' => 'Unknown Fallback',
                    ]);
                    $updateData['customer_id'] = $fallback->id;
                }

                if (! empty($row['status_pesanan'])) {
                    $newStatus = $this->normalizeStatus($row['status_pesanan']);
                    if ($existingOrder && isset($existingOrder->order_status)) {
                        $newStatus = $this->applyStateMachineProtection($existingOrder->order_status, $newStatus);
                    }
                    $updateData['order_status'] = $newStatus;
                    $updateData['order_substatus'] = $row['status_pesanan'];
                } else {
                    $updateData['order_status'] = $updateData['order_status'] ?? ($existingOrder->order_status ?? 'UNKNOWN');
                    $updateData['order_substatus'] = $updateData['order_substatus'] ?? ($existingOrder->order_substatus ?? null);
                }

                $updateData['raw_status'] = $row['status_pembatalan_pengembalian'] ?? ($updateData['raw_status'] ?? ($existingOrder->raw_status ?? null));
                $updateData['tracking_number'] = ($existingOrder->tracking_number ?? null) ?? ($row['no_resi'] ?? null);
                $updateData['shipping_provider'] = ($existingOrder->shipping_provider ?? null) ?? ($row['opsi_pengiriman'] ?? null);
                $updateData['payment_method'] = ($existingOrder->payment_method ?? null) ?? ($row['metode_pembayaran'] ?? null);

                $updateData['order_date'] = ! empty($row['waktu_pesanan_dibuat']) && $row['waktu_pesanan_dibuat'] !== '-'
                    ? Carbon::parse($row['waktu_pesanan_dibuat'])->toDateTimeString()
                    : ($existingOrder->order_date ?? now()->toDateTimeString());

                $updateData['paid_at'] = ! empty($row['waktu_pembayaran_dilakukan']) && $row['waktu_pembayaran_dilakukan'] !== '-'
                    ? Carbon::parse($row['waktu_pembayaran_dilakukan'])->toDateTimeString()
                    : ($existingOrder->paid_at ?? null);

                $updateData['completed_at'] = ! empty($row['waktu_pesanan_selesai']) && $row['waktu_pesanan_selesai'] !== '-'
                    ? Carbon::parse($row['waktu_pesanan_selesai'])->toDateTimeString()
                    : ($existingOrder->completed_at ?? null);

                $updateData['cancelled_at'] = ! empty($row['waktu_pembatalan']) && $row['waktu_pembatalan'] !== '-'
                    ? Carbon::parse($row['waktu_pembatalan'])->toDateTimeString()
                    : ($existingOrder->cancelled_at ?? null);

                $updateData['total_payment'] = $this->getSafeOrderLevelValue($row, 'total_pembayaran', $existingOrder->total_payment ?? null);
                $updateData['shipping_fee'] = $this->getSafeOrderLevelValue($row, 'ongkos_kirim_dibayar_oleh_pembeli', $existingOrder->shipping_fee ?? null);

                $updateData['currency'] = 'IDR';
                $updateData['source_type'] = 'excel';
                $updateData['raw_data'] = json_encode($row); // Must encode for bulk upsert

                if (! $existingOrder) {
                    $ordersToUpsert[$orderId] = $updateData;
                    $this->success_rows++;
                    // Mark as existing for this chunk so we don't count it twice
                    $existingOrders->put($orderId, true);
                    $orderChangeTracker[$orderId] = 'new';
                } else {
                    if ($existingOrder === true) {
                        $ordersToUpsert[$orderId] = $updateData;
                    } else {
                        $orderChanged = false;
                        $fieldsToCheck = [
                            'order_status', 'order_substatus', 'tracking_number',
                            'shipping_provider', 'payment_method', 'total_payment', 'shipping_fee', 'raw_data',
                        ];
                        foreach ($fieldsToCheck as $field) {
                            if ($existingOrder->{$field} != $updateData[$field]) {
                                $orderChanged = true;
                                break;
                            }
                        }

                        if ($orderChanged) {
                            $ordersToUpsert[$orderId] = $updateData;
                        }

                        // Only mark as changed if it wasn't already marked 'new' or 'changed' in a previous row of this chunk
                        if (! isset($orderChangeTracker[$orderId]) || $orderChangeTracker[$orderId] === 'unchanged') {
                            $orderChangeTracker[$orderId] = $orderChanged ? 'changed' : 'unchanged';
                        }
                    }
                }
            }

            // Execute Bulk Upsert for Orders
            if (! empty($ordersToUpsert)) {
                Order::upsert(
                    array_values($ordersToUpsert),
                    ['marketplace_account_id', 'external_order_id'],
                    [
                        'customer_id', 'order_status', 'order_substatus', 'raw_status',
                        'tracking_number', 'shipping_provider', 'payment_method',
                        'order_date', 'paid_at', 'completed_at', 'cancelled_at',
                        'total_payment', 'shipping_fee', 'raw_data',
                    ]
                );
            }

            // Refresh Orders to get their DB IDs for the items
            $refreshedOrders = Order::where('marketplace_account_id', $accountId)
                ->whereIn('external_order_id', array_keys($ordersToUpsert))
                ->get()->keyBy('external_order_id');

            $existingItems = OrderItem::whereIn('order_id', $refreshedOrders->pluck('id'))->get();
            $itemsToInsert = [];
            $stagedItemKeys = [];

            // Loop 2: Process Order Items
            foreach ($validRows as $row) {
                $orderId = (string) ($row['no_pesanan']);
                $order = $refreshedOrders->get($orderId);

                if (! $order) {
                    continue;
                } // Should not happen

                // Resolve Product
                $refSku = ! empty($row['nomor_referensi_sku']) && $row['nomor_referensi_sku'] !== '-' ? $row['nomor_referensi_sku'] : null;
                $parentSku = ! empty($row['sku_induk']) && $row['sku_induk'] !== '-' ? $row['sku_induk'] : null;
                $fallbackSku = 'SKU-'.substr(md5($row['nama_produk'] ?? 'unknown'), 0, 8);
                $sku = $refSku ?? $parentSku ?? $fallbackSku;

                $productName = $row['nama_produk'] ?? 'Unknown Product';
                $variantName = $row['nama_variasi'] ?? null;

                $productMarketplace = $productMarketplaces->get($sku);
                $product = $productMarketplace ? $productMarketplace->product : $masterProducts->get($sku);
                $productId = $product ? $product->id : null;
                $hpp = $product ? $product->hpp : null;

                $basePrice = isset($row['harga_awal']) && $row['harga_awal'] !== '' ? $this->parseNumber($row['harga_awal']) : 0;
                $qty = $this->parseNumber($row['jumlah'] ?? 1);
                $priceAfterDiscount = isset($row['harga_setelah_diskon']) && $row['harga_setelah_diskon'] !== '' ? $this->parseNumber($row['harga_setelah_diskon']) : $basePrice;
                $itemSubtotal = $priceAfterDiscount * $qty;
                $itemDiscount = ($basePrice - $priceAfterDiscount) * $qty;

                $itemDiscount = $itemDiscount > 0 ? $itemDiscount : 0;

                $itemKey = $order->id.'|'.md5($productName).'|'.md5($variantName ?? '');

                if (in_array($itemKey, $stagedItemKeys)) {
                    // Item was already added in this chunk from a duplicate row
                    $this->duplicate_rows++;

                    continue;
                }

                // Find existing item in memory
                $existingItem = $existingItems->first(function ($i) use ($order, $productName, $variantName) {
                    return $i->order_id === $order->id
                        && $i->product_name === $productName
                        && $i->variant_name === $variantName;
                });

                if ($existingItem) {
                    // Check if it really changed
                    $hasItemChanges = $existingItem->qty != $qty ||
                                  $existingItem->selling_price != $priceAfterDiscount ||
                                  $existingItem->subtotal != $itemSubtotal ||
                                  $existingItem->product_id != $productId;

                    if ($hasItemChanges) {
                        $existingItem->update([
                            'product_id' => $productId,
                            'marketplace_sku' => $sku,
                            'qty' => $qty,
                            'original_price' => $basePrice,
                            'selling_price' => $priceAfterDiscount,
                            'discount' => $itemDiscount,
                            'subtotal' => $itemSubtotal,
                            // Hanya update HPP jika HPP sebelumnya null (belum ada data) untuk menjaga histori
                            'hpp_snapshot' => ($existingItem->hpp_snapshot !== null) ? $existingItem->hpp_snapshot : $hpp,
                        ]);
                    }

                    $orderStatus = $orderChangeTracker[$orderId] ?? 'unchanged';
                    if ($hasItemChanges || $orderStatus === 'changed') {
                        $this->updated_rows++;
                        if ($orderStatus === 'changed') {
                            $orderChangeTracker[$orderId] = 'unchanged'; // Prevent double-counting order update
                        }
                    } else {
                        $this->duplicate_rows++;
                    }
                } else {
                    $stagedItemKeys[] = $itemKey;
                    $itemsToInsert[] = [
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'variant_name' => $variantName,
                        'marketplace_sku' => $sku,
                        'qty' => $qty,
                        'original_price' => $basePrice,
                        'selling_price' => $priceAfterDiscount,
                        'discount' => $itemDiscount,
                        'subtotal' => $itemSubtotal,
                        'hpp_snapshot' => $hpp,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $orderStatus = $orderChangeTracker[$orderId] ?? 'unchanged';
                    if ($orderStatus !== 'new') {
                        $this->updated_rows++; // Order existed, but item is new -> counts as update
                    }
                }

                $this->total_rows++;
            }

            if (! empty($itemsToInsert)) {
                OrderItem::insert($itemsToInsert);
            }

            // Sinkronisasi stok berdasarkan status order (Idempotent, Status-aware)
            // Aturan:
            //   PAID/SHIPPED/COMPLETED → potong stok (jika belum)
            //   CANCELLED/RETURNED     → kembalikan stok (jika sudah dipotong)
            //   PENDING/UNKNOWN        → tidak ada aksi
            try {
                $inventoryService = app(InventoryService::class);
                $ordersToSync = Order::with('items')
                    ->whereIn('id', $refreshedOrders->pluck('id'))
                    ->get();

                foreach ($ordersToSync as $ord) {
                    $inventoryService->syncOrderStockStatus($ord);
                }
            } catch (\Throwable $invEx) {
                \Log::error('Inventory syncOrderStockStatus Error: '.$invEx->getMessage(), [
                    'batch_id' => $this->batch->id,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            // Fallback to row-by-row processing to isolate the error
            $this->processRowsIndividually($rows);
        }
    }

    private function processRowsIndividually(Collection $rows)
    {
        foreach ($rows as $row) {
            DB::beginTransaction();
            try {
                // Instantiate a fresh importer for a single row chunk
                $importer = new self($this->batch);
                $importer->collection(collect([$row]));

                // Aggregate stats back to main chunk
                $this->success_rows += $importer->success_rows;
                $this->duplicate_rows += $importer->duplicate_rows;
                $this->updated_rows += $importer->updated_rows;
                // DO NOT aggregate total_rows because it will double count later

                DB::commit();
            } catch (\Throwable $rowException) {
                DB::rollBack();
                $this->error_rows++;

                $this->batch->importErrors()->create([
                    'row_number' => null, // Row number tracking inside chunk is lost, but we have the raw_data
                    'external_order_id' => $row['no_pesanan'] ?? 'UNKNOWN',
                    'error_type' => $this->mapErrorType($rowException),
                    'error_message' => substr($rowException->getMessage().' at Line '.$rowException->getLine(), 0, 250),
                    'raw_data' => $row, // Save the actual faulty row for Retry
                ]);

                \Log::error('Shopee Import Row Error: '.$rowException->getMessage(), [
                    'import_batch_id' => $this->batch->id,
                    'row_number' => $this->getRowNumber(),
                    'external_order_id' => $row['no_pesanan'] ?? 'UNKNOWN',
                ]);
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $this->batch->increment('total_rows', $this->total_rows);
                $this->batch->increment('success_rows', $this->success_rows);
                $this->batch->increment('duplicate_rows', $this->duplicate_rows);
                $this->batch->increment('updated_rows', $this->updated_rows);
                $this->batch->increment('error_rows', $this->error_rows);
            },
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function parseNumber($value)
    {
        if (empty($value) || $value === '-' || $value === '') {
            return 0.0;
        }
        $value = preg_replace('/[^0-9\,\.\-]/', '', (string) $value);
        $lastDot = strrpos($value, '.');
        $lastComma = strrpos($value, ',');
        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) {
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
        } elseif ($lastComma !== false) {
            if (preg_match('/,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastDot !== false) {
            if (preg_match('/\.\d{3}$/', $value)) {
                $value = str_replace('.', '', $value);
            }
        }

        return (float) $value;
    }

    private function getSafeOrderLevelValue($row, $key, $currentValue)
    {
        if (isset($row[$key]) && $row[$key] !== '') {
            $parsedValue = $this->parseNumber($row[$key]);
            if ($parsedValue > 0 || $currentValue === null || $currentValue === 0.0) {
                return $parsedValue;
            }
        }

        return $currentValue;
    }

    private function normalizeStatus($status)
    {
        $status = strtoupper($status);
        if (str_contains($status, 'BELUM DIBAYAR') || str_contains($status, 'UNPAID') || str_contains($status, 'PENDING')) {
            return 'PENDING';
        }
        if (str_contains($status, 'PERLU DIKIRIM') || str_contains($status, 'PAID') || str_contains($status, 'DIKEMAS')) {
            return 'PAID';
        }
        if (str_contains($status, 'DIKIRIM') || str_contains($status, 'SHIPPED') || str_contains($status, 'SEDANG DIKIRIM')) {
            return 'SHIPPED';
        }
        if (str_contains($status, 'SELESAI') || str_contains($status, 'COMPLETED') || str_contains($status, 'DELIVERED')) {
            return 'COMPLETED';
        }
        if (str_contains($status, 'BATAL') || str_contains($status, 'CANCEL') || str_contains($status, 'DIBATALKAN')) {
            return 'CANCELLED';
        }
        if (str_contains($status, 'PENGEMBALIAN') || str_contains($status, 'RETURN') || str_contains($status, 'RETUR')) {
            return 'RETURNED';
        }

        return 'UNKNOWN';
    }

    private function applyStateMachineProtection($oldStatus, $newStatus)
    {
        $hierarchy = [
            'UNKNOWN' => 0,
            'PENDING' => 1,
            'PAID' => 2,
            'SHIPPED' => 3,
            'COMPLETED' => 4,
            'CANCELLED' => 4,
            'RETURNED' => 4,
        ];

        $oldRank = $hierarchy[$oldStatus] ?? 0;
        $newRank = $hierarchy[$newStatus] ?? 0;

        if ($oldRank > $newRank) {
            return $oldStatus; // Protect from downgrade
        }

        return $newStatus;
    }
}
