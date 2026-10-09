<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'product_name',
        'variant_name',
        'category_id',
        'unit',
        'category',
        'brand',
        'hpp',
        'selling_price',
        'stock',
        'minimum_stock',
        'status',
        'image',
        'description',
    ];

    protected $casts = [
        'hpp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock' => 'float',
        'minimum_stock' => 'float',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function marketplaceAccounts()
    {
        return $this->belongsToMany(MarketplaceAccount::class, 'product_marketplace')
            ->withPivot('marketplace_sku', 'price', 'is_active', 'marketplace_product_id', 'marketplace_product_name', 'marketplace_variant')
            ->withTimestamps();
    }

    public function productMarketplaces()
    {
        return $this->hasMany(ProductMarketplace::class);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function hppHistories()
    {
        return $this->hasMany(ProductHppHistory::class)->orderBy('created_at', 'desc');
    }
}
