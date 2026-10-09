<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMarketplace;

class ProductMarketplaceService
{
    public function storeMapping(Product $product, array $data)
    {
        $exists = ProductMarketplace::where('marketplace_account_id', $data['marketplace_account_id'])
            ->where('marketplace_sku', $data['marketplace_sku'])
            ->first();

        if ($exists && $exists->product_id != $product->id) {
            throw new \Exception('SKU Shopee tersebut sudah terhubung dengan produk lain.');
        }

        if ($exists && $exists->product_id == $product->id) {
            throw new \Exception('Mapping untuk SKU Marketplace tersebut sudah ada di akun ini.');
        }

        $data['product_id'] = $product->id;
        $data['is_active'] = true;

        return ProductMarketplace::create($data);
    }

    public function destroyMapping($mappingId)
    {
        $mapping = ProductMarketplace::findOrFail($mappingId);
        $mapping->delete();
        return true;
    }
}
