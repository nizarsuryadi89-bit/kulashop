<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFinancial extends Model
{
    protected $fillable = [
        'order_id',
        'product_amount',
        'shipping_paid_by_buyer',
        'discount_seller',
        'discount_marketplace',
        'admin_fee',
        'order_processing_fee',
        'transaction_fee',
        'service_fee',
        'promotion_fee',
        'affiliate_fee',
        'other_fee',
        'refund_amount',
        'tax_amount',
        'total_payment',
        'total_income',
        'settlement_amount',
        'released_at',
        'raw_data',
    ];

    protected $casts = [
        'product_amount' => 'decimal:2',
        'shipping_paid_by_buyer' => 'decimal:2',
        'discount_seller' => 'decimal:2',
        'discount_marketplace' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'order_processing_fee' => 'decimal:2',
        'transaction_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'promotion_fee' => 'decimal:2',
        'affiliate_fee' => 'decimal:2',
        'other_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_payment' => 'decimal:2',
        'total_income' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'released_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
