<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    public function accounts()
    {
        return $this->hasMany(MarketplaceAccount::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, MarketplaceAccount::class);
    }
}
