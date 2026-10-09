<?php

namespace Database\Seeders;

use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            ['name' => 'Shopee', 'code' => 'shopee'],
            ['name' => 'TikTok Shop', 'code' => 'tiktok'],
            ['name' => 'Tokopedia', 'code' => 'tokopedia'],
        ];

        foreach ($platforms as $platformData) {
            $platform = Marketplace::updateOrCreate(
                ['code' => $platformData['code']],
                ['name' => $platformData['name']]
            );

            // Create a default account for each platform
            $accountName = $platformData['name'].' ERP';
            MarketplaceAccount::updateOrCreate(
                ['account_code' => Str::slug($accountName).'-'.$platform->id],
                [
                    'marketplace_id' => $platform->id,
                    'account_name' => $accountName,
                    'status' => 'active',
                    'tax_percentage' => 0.00,
                ]
            );
        }
    }
}
