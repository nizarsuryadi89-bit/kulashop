<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'account_name',
        'account_code',
        'status',
        'tax_percentage',
    ];

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function importBatches()
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_marketplace')
            ->withPivot('marketplace_sku', 'price', 'is_active', 'marketplace_product_id', 'marketplace_product_name', 'marketplace_variant')
            ->withTimestamps();
    }

    public function productMarketplaces()
    {
        return $this->hasMany(ProductMarketplace::class, 'marketplace_account_id');
    }
}
