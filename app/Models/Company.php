<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'address',
        'phone',
        'email',
        'font_family',
        'sidebar_theme',
        'sidebar_text_theme',
        'login_background',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function hasFeature($feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features) || (isset($features[$feature]) && $features[$feature] == true);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
