<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductHppHistory;
use Illuminate\Support\Facades\DB;

class HppService
{
    /**
     * Update product HPP and record history
     */
    public function updateProductHpp(Product $product, float $newHpp, string $reason, int $userId): void
    {
        DB::transaction(function () use ($product, $newHpp, $reason, $userId) {
            $oldHpp = $product->hpp;
            $product->update(['hpp' => $newHpp]);

            ProductHppHistory::create([
                'product_id' => $product->id,
                'old_hpp' => $oldHpp,
                'new_hpp' => $product->hpp,
                'reason' => $reason,
                'changed_by' => $userId,
                'effective_at' => now(),
            ]);
        });
    }

    /**
     * Calculate HPP Summary grouped by Product/SKU
     */
    public function calculateProductHppSummary(string $startDate, string $endDate, ?string $status, ?string $search = null)
    {
        $itemsQuery = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.sku',
                DB::raw('COALESCE(products.product_name, order_items.product_name) as product_name'),
                'order_items.hpp_snapshot',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.qty * COALESCE(order_items.hpp_snapshot, 0)) as subtotal_hpp')
            )
            ->whereDate('orders.order_date', '>=', $startDate)
            ->whereDate('orders.order_date', '<=', $endDate);

        if (!empty($status)) {
            $itemsQuery->where('orders.order_status', $status);
        }

        if (!empty($search)) {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('products.sku', 'like', "%{$search}%")
                  ->orWhere('products.product_name', 'like', "%{$search}%")
                  ->orWhere('order_items.product_name', 'like', "%{$search}%");
            });
        }

        $itemsQuery->groupBy('products.sku', 'product_name', 'order_items.hpp_snapshot');
        
        return $itemsQuery->orderBy('total_qty', 'desc');
    }

    /**
     * Get base query for KPIs like total Qty, total HPP, etc
     */
    public function getHppKpiQuery(string $startDate, string $endDate, ?string $status, ?string $search = null)
    {
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'order_items.*',
                'orders.order_date',
                'orders.order_status',
                'products.sku',
                'products.product_name as master_product_name'
            )
            ->whereDate('orders.order_date', '>=', $startDate)
            ->whereDate('orders.order_date', '<=', $endDate);

        if (!empty($status)) {
            $query->where('orders.order_status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('products.sku', 'like', "%{$search}%")
                  ->orWhere('products.product_name', 'like', "%{$search}%")
                  ->orWhere('order_items.product_name', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * Calculate how many order items are missing an HPP snapshot in the given criteria
     */
    public function getProductsWithoutHpp(string $startDate, string $endDate, ?string $status, ?string $search = null): int
    {
        return $this->getHppKpiQuery($startDate, $endDate, $status, $search)
                    ->whereNull('order_items.hpp_snapshot')
                    ->count();
    }

    /**
     * Sync missing HPP for old orders for a given month
     */
    public function syncMissingHpp(string $month): int
    {
        $orders = Order::where('order_status', 'COMPLETED')
            ->where('order_date', 'like', "{$month}%")
            ->with('items.product')
            ->get();

        $updatedCount = 0;
        
        // Cache mapping to resolve unlinked items retroactively
        $marketplaceMappings = \App\Models\ProductMarketplace::with('product')->get();
        $masterProducts = \App\Models\Product::get()->keyBy('sku');

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // If product is not linked yet, try to link it retroactively
                if (!$item->product_id) {
                    $sku = $item->marketplace_sku;
                    
                    $productMarketplace = $marketplaceMappings->first(function($map) use ($sku, $order) {
                        return $map->marketplace_sku === $sku && $map->marketplace_account_id === $order->marketplace_account_id;
                    });
                    
                    $product = $productMarketplace ? $productMarketplace->product : ($masterProducts[$sku] ?? null);
                    
                    if ($product) {
                        $item->product_id = $product->id;
                        $item->save(); 
                        $item->setRelation('product', $product);
                    }
                }

                // Lakukan backfill untuk order yang hpp_snapshot-nya NULL atau 0.
                if ($item->product && ($item->hpp_snapshot === null || $item->hpp_snapshot == 0)) {
                    // Update hanya jika HPP master produk sudah di-set lebih dari 0
                    if ($item->product->hpp > 0) {
                        $item->hpp_snapshot = $item->product->hpp;
                        $item->save();
                        $updatedCount++;
                    }
                }
            }
        }
        
        return $updatedCount;
    }
}
