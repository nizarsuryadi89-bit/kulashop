<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'status'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
