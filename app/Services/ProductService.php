<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;

class ProductService
{
    public function getUnmappedSkus()
    {
        return OrderItem::selectRaw('marketplace_sku, MAX(product_name) as product_name, MAX(variant_name) as variant_name, COUNT(DISTINCT order_id) as total_orders, SUM(qty) as total_qty')
            ->whereNotIn('marketplace_sku', function ($query) {
                $query->select('marketplace_sku')->from('product_marketplace');
            })
            ->whereNotNull('marketplace_sku')
            ->where('marketplace_sku', '!=', '')
            ->groupBy('marketplace_sku')
            ->orderByDesc('total_orders')
            ->paginate(20);
    }

    public function syncUnmappedSkus(): int
    {
        $unmappedItems = OrderItem::selectRaw('order_items.marketplace_sku, orders.marketplace_account_id, MAX(order_items.product_name) as product_name, MAX(order_items.selling_price) as selling_price')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('order_items.product_id')
            ->whereNotNull('order_items.marketplace_sku')
            ->where('order_items.marketplace_sku', '!=', '')
            ->groupBy('order_items.marketplace_sku', 'orders.marketplace_account_id')
            ->get();

        $syncedCount = 0;

        foreach ($unmappedItems as $item) {
            // Check if product exists internally
            $product = Product::where('sku', $item->marketplace_sku)->first();

            if (!$product) {
                // Auto create product
                $product = Product::create([
                    'sku' => $item->marketplace_sku,
                    'product_name' => $item->product_name ?? 'Produk Baru ' . $item->marketplace_sku,
                    'hpp' => 0,
                    'selling_price' => $item->selling_price ?? 0,
                    'stock' => 0,
                    'minimum_stock' => 0,
                    'status' => 'active'
                ]);
            }

            // Create mapping if not exists
            \App\Models\ProductMarketplace::updateOrCreate([
                'marketplace_account_id' => $item->marketplace_account_id,
                'marketplace_sku' => $item->marketplace_sku,
            ], [
                'product_id' => $product->id,
                'is_active' => true
            ]);

            // Update all past unmapped order items for this account + SKU combination
            OrderItem::where('marketplace_sku', $item->marketplace_sku)
                ->whereNull('product_id')
                ->whereHas('order', function($q) use ($item) {
                    $q->where('marketplace_account_id', $item->marketplace_account_id);
                })
                ->update(['product_id' => $product->id]);

            $syncedCount++;
        }

        return $syncedCount;
    }

    public function getFilteredProducts($search, $status, $category, $mappingStatus)
    {
        $query = Product::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('variant_name', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($mappingStatus === 'mapped') {
            $query->has('productMarketplaces');
        } elseif ($mappingStatus === 'unmapped') {
            $query->doesntHave('productMarketplaces');
        }

        return $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
    }
}
