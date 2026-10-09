<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'marketplace_sku',
        'product_name',
        'variant_name',
        'qty',
        'original_price',
        'selling_price',
        'discount',
        'subtotal',
        'hpp_snapshot',
        'returned_qty',
    ];

    protected $casts = [
        'qty' => 'float',
        'original_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'hpp_snapshot' => 'decimal:2',
        'returned_qty' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
