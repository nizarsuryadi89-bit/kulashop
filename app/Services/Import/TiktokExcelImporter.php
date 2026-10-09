<?php

namespace App\Services\Import;

use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\MapsImportErrors;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TiktokExcelImporter implements ToModel, WithChunkReading, WithHeadingRow
{
    use MapsImportErrors;
    use RemembersRowNumber;

    protected $batch;

    public function __construct(ImportBatch $batch)
    {
        $this->batch = $batch;
    }

    public function model(array $row): Model|array|null
    {
        try {
            $orderId = $row['order_id'] ?? null;
            if (! $orderId || strtolower($orderId) === 'order id' || strtolower($orderId) === 'order_id' || strtolower($orderId) === 'platform unique order id.') {
                return null;
            }

            // 1. Upsert Customer
            $customerName = $row['recipient'] ?? 'Unknown';
            $customerPhone = $row['phone'] ?? null;
            $customerCity = $row['regency_and_city'] ?? null;
            $customerUsername = $row['buyer_username'] ?? $row['username'] ?? null;
            $postalCode = $row['zipcode'] ?? null;

            $customer = null;
            if (! empty($customerUsername) && $customerUsername != '-') {
                $customer = Customer::where('username', $customerUsername)->first();

                if ($customer) {
                    // Update existing customer
                    $customer->update([
                        'customer_name' => $customerName,
                        'phone' => $customer->phone ?? $customerPhone,
                        'address' => $customer->address ?? ($row['address'] ?? null),
                        'city' => $customer->city ?? $customerCity,
                        'province' => $customer->province ?? ($row['province'] ?? null),
                        'district' => $customer->district ?? ($row['districts'] ?? null),
                        'postal_code' => $customer->postal_code ?? $postalCode,
                    ]);
                } else {
                    $customer = Customer::create([
                        'customer_code' => 'CUST-'.strtoupper(substr(uniqid(), -6)),
                        'username' => $customerUsername,
                        'customer_name' => $customerName,
                        'phone' => $customerPhone,
                        'address' => $row['address'] ?? null,
                        'city' => $customerCity,
                        'province' => $row['province'] ?? null,
                        'district' => $row['districts'] ?? null,
                        'postal_code' => $postalCode,
                    ]);
                }
            } else {
                // Fallback
                $customer = Customer::firstOrCreate(
                    ['customer_name' => $customerName, 'city' => $customerCity],
                    [
                        'customer_code' => 'CUST-'.strtoupper(substr(uniqid(), -6)),
                        'phone' => $customerPhone,
                        'address' => $row['address'] ?? null,
                        'province' => $row['province'] ?? null,
                        'district' => $row['districts'] ?? null,
                        'postal_code' => $postalCode,
                    ]
                );
            }

            // 2. Upsert Product
            $sku = ! empty($row['seller_sku']) ? $row['seller_sku'] : (! empty($row['sku_id']) ? $row['sku_id'] : 'SKU-'.substr(md5($row['product_name'] ?? 'unknown'), 0, 8));
            $productName = $row['product_name'] ?? 'Unknown Product';
            $basePrice = isset($row['sku_unit_original_price']) ? floatval(str_replace(',', '', $row['sku_unit_original_price'])) : 0;

            $product = null;
            if ($sku && $sku != '-') {
                $product = Product::firstOrCreate(
                    ['sku' => $sku],
                    [
                        'product_name' => $productName,
                        'variant_name' => $row['variation'] ?? null,
                        'hpp' => $basePrice,
                        'selling_price' => $basePrice,
                    ]
                );
            }

            // 3. Upsert Order
            $orderDateStr = $row['created_time'] ?? null;
            $orderDate = $this->parseTiktokDate($orderDateStr) ?: now();

            $paidDateStr = $row['paid_time'] ?? null;
            $paidDate = $this->parseTiktokDate($paidDateStr);

            $completedDateStr = $row['delivered_time'] ?? null;
            $completedDate = $this->parseTiktokDate($completedDateStr);

            $cancelledDateStr = $row['cancel_time'] ?? null;
            $cancelledDate = $this->parseTiktokDate($cancelledDateStr);

            $grandTotal = isset($row['order_amount']) ? floatval(str_replace(',', '', $row['order_amount'])) : 0;
            $shippingFee = isset($row['shipping_fee_after_discount']) ? floatval(str_replace(',', '', $row['shipping_fee_after_discount'])) : 0;
            $marketplaceFee = isset($row['buyer_service_fee']) ? floatval(str_replace(',', '', $row['buyer_service_fee'])) : 0;
            $marketplaceFee += isset($row['handling_fee']) ? floatval(str_replace(',', '', $row['handling_fee'])) : 0;

            $order = Order::updateOrCreate(
                ['marketplace_account_id' => $this->batch->marketplace_account_id, 'external_order_id' => $orderId],
                [
                    'customer_id' => $customer->id,
                    'order_status' => $this->normalizeStatus($row['order_status'] ?? ''),
                    'order_substatus' => $row['order_status'] ?? '',
                    'order_date' => $orderDate,
                    'paid_at' => $paidDate,
                    'completed_at' => $completedDate,
                    'cancelled_at' => $cancelledDate,
                    'total_payment' => $grandTotal,
                    'shipping_fee' => $shippingFee,
                    'currency' => 'IDR',
                    'source_type' => 'tiktok_excel',
                    'raw_data' => $row,
                ]
            );

            // 4. Upsert Order Items
            $qty = intval($row['quantity'] ?? 1);
            $priceAfterDiscount = isset($row['sku_subtotal_after_discount']) ? floatval(str_replace(',', '', $row['sku_subtotal_after_discount'])) : $basePrice;
            $itemSubtotal = $priceAfterDiscount * $qty;

            $itemDiscount = isset($row['sku_seller_discount']) ? floatval(str_replace(',', '', $row['sku_seller_discount'])) : 0;
            $itemDiscount += isset($row['sku_platform_discount']) ? floatval(str_replace(',', '', $row['sku_platform_discount'])) : 0;

            $orderItem = OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'product_name' => $productName],
                [
                    'product_id' => $product ? $product->id : null,
                    'marketplace_sku' => $sku,
                    'variant_name' => $row['variation'] ?? null,
                    'qty' => $qty,
                    'original_price' => $basePrice,
                    'selling_price' => $priceAfterDiscount,
                    'discount' => $itemDiscount > 0 ? $itemDiscount : 0,
                    'subtotal' => $itemSubtotal,
                    'hpp_snapshot' => $product ? $product->hpp : 0,
                ]
            );

            $this->batch->increment('total_rows');

            $isNew = $order->wasRecentlyCreated || $orderItem->wasRecentlyCreated;
            $isUpdated = $order->wasChanged() || $orderItem->wasChanged();

            if ($isNew) {
                $this->batch->increment('success_rows');
            } elseif ($isUpdated) {
                $this->batch->increment('updated_rows');
            } else {
                $this->batch->increment('duplicate_rows');
            }
        } catch (\Throwable $e) {
            $this->batch->increment('total_rows');
            $this->batch->increment('error_rows');

            $this->batch->importErrors()->create([
                'row_number' => $this->getRowNumber(),
                'external_order_id' => $orderId ?? null,
                'error_type' => $this->mapErrorType($e),
                'error_message' => substr($e->getMessage(), 0, 250),
                'raw_data' => $row,
            ]);

            // Log row error but continue parsing other rows
            \Log::error('Tiktok Import Row Error: '.$e->getMessage(), [
                'import_batch_id' => $this->batch->id,
                'row_number' => $this->getRowNumber(),
                'external_order_id' => $orderId ?? null,
            ]);
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function normalizeStatus($status)
    {
        $status = strtoupper($status);
        if (str_contains($status, 'UNPAID') || str_contains($status, 'PENDING') || str_contains($status, 'BELUM DIBAYAR') || str_contains($status, 'MENUNGGU PEMBAYARAN')) {
            return 'PENDING';
        }
        if (str_contains($status, 'AWAITING') || str_contains($status, 'PAID') || str_contains($status, 'DIKEMAS') || str_contains($status, 'PERLU DIKIRIM') || str_contains($status, 'MENUNGGU PENGIRIMAN')) {
            return 'PAID';
        }
        if (str_contains($status, 'SHIPPED') || str_contains($status, 'IN TRANSIT') || str_contains($status, 'DIKIRIM') || str_contains($status, 'SEDANG DIKIRIM')) {
            return 'SHIPPED';
        }
        if (str_contains($status, 'DELIVERED') || str_contains($status, 'COMPLETED') || str_contains($status, 'SELESAI')) {
            return 'COMPLETED';
        }
        if (str_contains($status, 'CANCEL') || str_contains($status, 'DIBATALKAN') || str_contains($status, 'BATAL')) {
            return 'CANCELLED';
        }
        if (str_contains($status, 'RETURN') || str_contains($status, 'DIKEMBALIKAN') || str_contains($status, 'RETUR') || str_contains($status, 'PENGEMBALIAN')) {
            return 'RETURNED';
        }

        return 'UNKNOWN';
    }

    private function parseTiktokDate($dateStr)
    {
        if (empty($dateStr) || $dateStr === '-') {
            return null;
        }

        try {
            // Try explicit format first: DD/MM/YYYY HH:MM:SS
            return Carbon::createFromFormat('d/m/Y H:i:s', $dateStr);
        } catch (\Exception $e) {
            try {
                // Fallback to Carbon's default parsing if format doesn't match
                // Note: For dates with slashes, PHP defaults to MM/DD/YYYY, so we replace them with dashes to force DD-MM-YYYY
                $dateStrDashes = str_replace('/', '-', $dateStr);

                return Carbon::parse($dateStrDashes);
            } catch (\Exception $e2) {
                return null;
            }
        }
    }
}
