<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Generate unique stock movement number (e.g. SM-20260919-0001).
     */
    public function generateMovementNumber(): string
    {
        $datePrefix = date('Ymd');
        $prefix = 'SM-'.$datePrefix.'-';

        $lastMovement = StockMovement::where('movement_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($lastMovement) {
            $parts = explode('-', $lastMovement->movement_number);
            $nextSeq = (int) end($parts) + 1;
        }

        return $prefix.str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get or create warehouse stock record.
     */
    public function getOrCreateWarehouseStock(int $warehouseId, int $productId): WarehouseStock
    {
        return WarehouseStock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['qty' => 0]
        );
    }

    /**
     * Record Stock In (Barang Masuk).
     */
    public function increaseStock(
        int $productId,
        int $warehouseId,
        float $qty,
        ?string $notes = null,
        string $referenceType = 'manual_in',
        ?int $referenceId = null,
        ?int $userId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Jumlah stok masuk harus lebih dari 0.');
        }

        $warehouse = Warehouse::find($warehouseId);
        if (! $warehouse || $warehouse->status !== 'active') {
            throw new \Exception('Transaksi gagal: Gudang tidak aktif.');
        }

        return DB::transaction(function () use ($productId, $warehouseId, $qty, $notes, $referenceType, $referenceId, $userId) {
            $whStock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['qty' => 0]
            );

            // Lock row
            $whStock = WarehouseStock::where('id', $whStock->id)->lockForUpdate()->first();

            $prevStock = $whStock->qty;
            $newStock = $prevStock + $qty;

            $whStock->update(['qty' => $newStock]);

            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $referenceType === 'transfer_in' ? 'transfer' : 'in',
                'qty' => $qty,
                'stock_before' => $prevStock,
                'stock_after' => $newStock,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $notes ?? 'Penerimaan Stok Masuk',
                'created_by' => $userId ?? auth()->id(),
            ]);

            $this->syncAggregateProductStock($productId);

            return $movement;
        });
    }

    /**
     * Record Stock Out (Barang Keluar).
     */
    public function decreaseStock(
        int $productId,
        int $warehouseId,
        float $qty,
        ?string $notes = null,
        string $referenceType = 'manual_out',
        ?int $referenceId = null,
        ?int $userId = null,
        bool $allowNegative = false
    ): StockMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Jumlah stok keluar harus lebih dari 0.');
        }

        $warehouse = Warehouse::find($warehouseId);
        if (! $warehouse || $warehouse->status !== 'active') {
            throw new \Exception('Transaksi gagal: Gudang tidak aktif.');
        }

        return DB::transaction(function () use ($productId, $warehouseId, $qty, $notes, $referenceType, $referenceId, $userId, $allowNegative) {
            $whStock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['qty' => 0]
            );

            $whStock = WarehouseStock::where('id', $whStock->id)->lockForUpdate()->first();

            $prevStock = $whStock->qty;

            if (! $allowNegative && $prevStock < $qty) {
                throw new \Exception("Stok tidak mencukupi di gudang ini. Sisa stok: {$prevStock}, diminta: {$qty}.");
            }

            $newStock = $prevStock - $qty;

            $whStock->update(['qty' => $newStock]);

            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $referenceType === 'transfer_out' ? 'transfer' : 'out',
                'qty' => $qty,
                'stock_before' => $prevStock,
                'stock_after' => $newStock,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $notes ?? 'Pengeluaran Stok',
                'created_by' => $userId ?? auth()->id(),
            ]);

            $this->syncAggregateProductStock($productId);

            return $movement;
        });
    }

    /**
     * Record Stock Adjustment (Stock Opname).
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        float $actualStock,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        if ($actualStock < 0) {
            throw new \InvalidArgumentException('Jumlah stok fisik tidak boleh negatif.');
        }

        $warehouse = Warehouse::find($warehouseId);
        if (! $warehouse || $warehouse->status !== 'active') {
            throw new \Exception('Transaksi gagal: Gudang tidak aktif.');
        }

        return DB::transaction(function () use ($productId, $warehouseId, $actualStock, $notes, $userId) {
            $whStock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['qty' => 0]
            );

            $whStock = WarehouseStock::where('id', $whStock->id)->lockForUpdate()->first();

            $prevStock = $whStock->qty;
            $difference = $actualStock - $prevStock;

            $whStock->update(['qty' => $actualStock]);

            $defaultNote = $difference >= 0
                ? "Penyesuaian Stock Opname (+{$difference})"
                : "Penyesuaian Stock Opname ({$difference})";

            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'adjustment',
                'qty' => abs($difference),
                'stock_before' => $prevStock,
                'stock_after' => $actualStock,
                'reference_type' => 'opname',
                'reference_id' => null,
                'note' => $notes ?? $defaultNote,
                'created_by' => $userId ?? auth()->id(),
            ]);

            $this->syncAggregateProductStock($productId);

            return $movement;
        });
    }

    /**
     * Transfer Stock (Pindah Gudang).
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $qty,
        ?string $notes = null,
        ?int $userId = null
    ): void {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Jumlah transfer harus lebih dari 0.');
        }

        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('Gudang asal dan tujuan tidak boleh sama.');
        }

        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $qty, $notes, $userId) {
            // 1. Keluarkan dari gudang asal
            $this->decreaseStock(
                productId: $productId,
                warehouseId: $fromWarehouseId,
                qty: $qty,
                notes: $notes ?? 'Transfer Keluar',
                referenceType: 'transfer_out',
                referenceId: $toWarehouseId,
                userId: $userId,
                allowNegative: false
            );

            // 2. Masukkan ke gudang tujuan
            $this->increaseStock(
                productId: $productId,
                warehouseId: $toWarehouseId,
                qty: $qty,
                notes: $notes ?? 'Transfer Masuk',
                referenceType: 'transfer_in',
                referenceId: $fromWarehouseId,
                userId: $userId
            );
        });
    }

    /**
     * Status-based trigger: decide whether to deduct or restore stock.
     *
     * Aturan:
     *   PAID / SHIPPED / COMPLETED → potong stok (jika belum)
     *   CANCELLED / RETURNED       → kembalikan stok (jika sudah dipotong)
     *   PENDING / UNKNOWN          → tidak ada aksi
     */
    public function syncOrderStockStatus(Order $order, ?int $warehouseId = null): void
    {
        $statusesToDeduct  = ['PAID', 'SHIPPED', 'COMPLETED'];
        $statusesToRestore = ['CANCELLED']; // RETURNED dihilangkan sementara agar tidak menambah stok berasumsi fisik barang sudah sampai

        $status = strtoupper($order->order_status ?? '');

        if (in_array($status, $statusesToDeduct)) {
            $this->deductOrderStock($order, $warehouseId);
        } elseif (in_array($status, $statusesToRestore)) {
            $this->restoreOrderStock($order, $warehouseId);
        }
        // PENDING / RETURNED / UNKNOWN → tidak ada aksi stok otomatis
    }

    /**
     * Potong stok untuk order Shopee.
     * Idempotent: cek order->stock_deducted (bukan StockMovement) sebagai sumber kebenaran.
     */
    public function deductOrderStock(Order $order, ?int $warehouseId = null): int
    {
        // Guard: sudah pernah dipotong — tidak duplikasi
        if ($order->stock_deducted) {
            return 0;
        }

        $warehouse = $this->resolveWarehouse($warehouseId);

        if (! $warehouse) {
            \Log::warning("Cannot deduct stock for order #{$order->id}: No active warehouse found.");

            return 0;
        }

        $deductedCount = 0;

        DB::transaction(function () use ($order, $warehouse, &$deductedCount) {
            // Lock the order row to prevent concurrent duplicate deductions
            $fresh = Order::lockForUpdate()->find($order->id);
            if ($fresh->stock_deducted) {
                return; // Race-condition guard
            }

            foreach ($order->items as $item) {
                if (! $item->product_id || $item->qty <= 0) {
                    continue;
                }

                // allowNegative = true: pesanan online boleh menyebabkan stok minus (backorder)
                $this->decreaseStock(
                    $item->product_id,
                    $warehouse->id,
                    (float) $item->qty,
                    "Order Shopee: {$order->external_order_id}",
                    'order',
                    $order->id,
                    null,
                    true
                );

                $deductedCount++;
            }

            // Tandai sebagai sudah dipotong — sumber kebenaran tunggal
            $fresh->update(['stock_deducted' => true]);
            $order->stock_deducted = true; // Sync in-memory object
        });

        return $deductedCount;
    }

    /**
     * Kembalikan stok untuk order yang dibatalkan atau diretur.
     * Idempotent: hanya berjalan jika order->stock_deducted = true.
     */
    public function restoreOrderStock(Order $order, ?int $warehouseId = null): int
    {
        // Guard: stok belum pernah dipotong — tidak perlu restore
        if (! $order->stock_deducted) {
            return 0;
        }

        $warehouse = $this->resolveWarehouse($warehouseId);

        if (! $warehouse) {
            \Log::warning("Cannot restore stock for order #{$order->id}: No active warehouse found.");

            return 0;
        }

        $restoredCount = 0;

        DB::transaction(function () use ($order, $warehouse, &$restoredCount) {
            $fresh = Order::lockForUpdate()->find($order->id);
            if (! $fresh->stock_deducted) {
                return; // Race-condition guard
            }

            foreach ($order->items as $item) {
                if (! $item->product_id || $item->qty <= 0) {
                    continue;
                }

                $this->increaseStock(
                    $item->product_id,
                    $warehouse->id,
                    (float) $item->qty,
                    "Batal/Retur Order Shopee: {$order->external_order_id}",
                    'order_return',
                    $order->id,
                    null
                );

                $restoredCount++;
            }

            // Reset flag — stok sudah dikembalikan
            $fresh->update(['stock_deducted' => false]);
            $order->stock_deducted = false;
        });

        return $restoredCount;
    }

    /**
     * Resolve target warehouse: parameter > default warehouse > first active.
     */
    private function resolveWarehouse(?int $warehouseId): ?Warehouse
    {
        if ($warehouseId) {
            return Warehouse::find($warehouseId);
        }

        return Warehouse::where('is_default', true)->where('status', 'active')->first()
            ?? Warehouse::where('status', 'active')->first();
    }

    /**
     * Sync aggregate stock to products table (to keep Step 9 & 10 consistent).
     */
    public function syncAggregateProductStock(int $productId): void
    {
        $totalStock = WarehouseStock::where('product_id', $productId)->sum('qty');
        Product::where('id', $productId)->update(['stock' => $totalStock]);
    }

    /**
     * Get Stock Card history for a product.
     */
    public function getStockCard(
        int $productId,
        ?int $warehouseId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = StockMovement::with(['warehouse', 'user'])
            ->where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();
    }

    /**
     * Get Low Stock products.
     */
    public function getLowStockProducts(?int $warehouseId = null, int $perPage = 20)
    {
        $query = Product::where('status', 'active')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock', 'asc');

        return $query->paginate($perPage)->withQueryString();
    }
}
