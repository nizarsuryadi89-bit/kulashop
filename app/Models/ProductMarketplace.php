<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMarketplace extends Model
{
    protected $table = 'product_marketplace';

    protected $fillable = [
        'product_id',
        'marketplace_account_id',
        'marketplace_product_id',
        'marketplace_sku',
        'marketplace_product_name',
        'marketplace_variant',
        'price',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplaceAccount()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }
}
