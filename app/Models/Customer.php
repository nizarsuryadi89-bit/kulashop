<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_account_id',
        'customer_code',
        'username',
        'customer_name',
        'phone',
        'address',
        'district',
        'city',
        'province',
        'postal_code',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->customer_name ?: $this->username;
    }
}
