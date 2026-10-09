<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::firstOrCreate([
            'code' => 'GDG-UTAMA',
        ], [
            'name' => 'Gudang Utama',
            'address' => 'Kantor Pusat & Gudang Utama',
            'is_default' => true,
            'status' => 'active',
        ]);
    }
}
