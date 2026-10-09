<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'external_order_id',
        'customer_id',
        'subtotal',
        'discount',
        'shipping_fee',
        'marketplace_fee',
        'total_payment',
        'currency',
        'order_status',
        'order_substatus',
        'raw_status',
        'tracking_number',
        'shipping_provider',
        'payment_method',
        'source_type',
        'order_date',
        'paid_at',
        'completed_at',
        'cancelled_at',
        'stock_deducted',
        'raw_data',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'stock_deducted' => 'boolean',
        'raw_data' => 'array',
    ];

    public function marketplaceAccount()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function financial()
    {
        return $this->hasOne(OrderFinancial::class);
    }

    public function getCalculatedTaxAttribute()
    {
        $taxPercentage = $this->marketplaceAccount->tax_percentage ?? 0;
        $shippingFee = $this->shipping_fee ?? 0;

        // Tax is calculated from (Grand Total - Shipping Fee) which is basically the Subtotal
        return (($this->total_payment - $shippingFee) * $taxPercentage) / 100;
    }

    public function getNetRevenueAttribute()
    {
        // Net Revenue = Grand Total - Shipping Fee (paid by buyer) - Marketplace Admin Fee - Calculated Tax
        $shippingFee = $this->shipping_fee ?? 0;
        $adminFee = $this->marketplace_fee ?? 0;
        $tax = $this->calculated_tax;

        return $this->total_payment - $shippingFee - $adminFee - $tax;
    }

    public function getTotalHppAttribute()
    {
        // Jika ada item yang HPP-nya NULL, jangan paksa menjadi 0.
        // Order ini secara keseluruhan belum bisa dihitung HPP Total-nya secara valid.
        if ($this->hpp_status === 'Incomplete') {
            return null; 
        }

        if ($this->relationLoaded('items')) {
            return $this->items->sum(function ($item) {
                $effectiveQty = $item->qty;
                if ($item->returned_qty !== null) {
                    $effectiveQty = max(0, $item->qty - $item->returned_qty);
                }
                return $effectiveQty * $item->hpp_snapshot;
            });
        }

        return $this->items()->sum(\Illuminate\Support\Facades\DB::raw('hpp_snapshot * qty'));
    }

    public function getHppStatusAttribute()
    {
        if ($this->relationLoaded('items')) {
            return $this->items->contains(fn($item) => $item->hpp_snapshot === null) ? 'Incomplete' : 'Complete';
        }

        return $this->items()->whereNull('hpp_snapshot')->exists() ? 'Incomplete' : 'Complete';
    }

    public function getIncomeStatusAttribute()
    {
        return $this->financial ? 'Complete' : 'Incomplete';
    }

    public function getGrossProfitAttribute(): ?float
    {
        return app(\App\Services\ProfitService::class)->getOrderProfit($this);
    }

    public function getProfitStatusAttribute()
    {
        return app(\App\Services\ProfitService::class)->getOrderProfitStatus($this);
    }

    public function getGrossMarginAttribute()
    {
        return app(\App\Services\ProfitService::class)->getOrderMargin($this);
    }
}
