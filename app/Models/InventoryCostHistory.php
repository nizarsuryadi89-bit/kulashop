<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCostHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'old_average_cost',
        'new_average_cost',
        'old_quantity',
        'new_quantity',
        'old_inventory_value',
        'new_inventory_value',
        'reference_type',
        'reference_id',
        'effective_at',
    ];

    protected $casts = [
        'effective_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
