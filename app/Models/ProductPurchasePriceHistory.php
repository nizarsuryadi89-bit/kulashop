<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPurchasePriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'goods_receipt_id',
        'purchase_price',
        'qty',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'qty' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }
}
