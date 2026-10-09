<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = \App\Models\Company::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Kulashop',
                'logo' => 'companies/logo-konsep-1.png',
                // Keep background dynamic or set it to null so it defaults
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@erpolshop.com'],
            [
                'name' => 'Owner',
                'password' => bcrypt('password'),
                'role' => 'owner',
                'company_id' => $company->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@erpolshop.com'],
            [
                'name' => 'Admin Olshop',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'company_id' => $company->id,
            ]
        );

        $this->call([
            MarketplaceSeeder::class,
            WarehouseSeeder::class,
        ]);
    }
}
