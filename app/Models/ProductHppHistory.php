<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductHppHistory extends Model
{
    protected $fillable = [
        'product_id',
        'old_hpp',
        'new_hpp',
        'reason',
        'changed_by',
        'effective_at',
    ];

    protected $casts = [
        'old_hpp' => 'decimal:2',
        'new_hpp' => 'decimal:2',
        'effective_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
