<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductMarketplace;
use Illuminate\Database\Seeder;

class DatabaseTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Marketplace
        $marketplace = Marketplace::firstOrCreate(
            ['code' => 'TEST_MP'],
            ['name' => 'Test Marketplace']
        );

        // MarketplaceAccount
        $account = MarketplaceAccount::firstOrCreate(
            ['account_code' => 'TEST_ACC_01'],
            [
                'marketplace_id' => $marketplace->id,
                'account_name' => 'Test Account 1',
                'status' => 'active',
                'tax_percentage' => 10,
            ]
        );

        // Customer
        $customer = Customer::firstOrCreate(
            ['customer_code' => 'CUST_TEST_01'],
            [
                'username' => 'testuser',
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'address' => 'Jl. Test No. 1',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
            ]
        );

        // Product
        $product = Product::firstOrCreate(
            ['sku' => 'PROD_TEST_01'],
            [
                'product_name' => 'Test Product',
                'hpp' => 50000,
                'selling_price' => 100000,
                'stock' => 100,
                'status' => 'active',
            ]
        );

        // ProductMarketplace
        ProductMarketplace::firstOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'marketplace_sku' => 'SKU_TEST_MP_01',
            ],
            [
                'product_id' => $product->id,
                'marketplace_product_name' => 'MP Test Product',
                'price' => 100000,
                'is_active' => true,
            ]
        );

        // Order
        $order = Order::firstOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'external_order_id' => 'ORD_TEST_001',
            ],
            [
                'customer_id' => $customer->id,
                'subtotal' => 100000,
                'discount' => 0,
                'shipping_fee' => 10000,
                'total_payment' => 110000,
                'currency' => 'IDR',
                'order_status' => 'completed',
                'order_date' => now(),
            ]
        );

        // OrderItem
        OrderItem::firstOrCreate(
            [
                'order_id' => $order->id,
                'product_id' => $product->id,
            ],
            [
                'marketplace_sku' => 'SKU_TEST_MP_01',
                'product_name' => 'Test Product',
                'qty' => 1,
                'original_price' => 100000,
                'selling_price' => 100000,
                'discount' => 0,
                'subtotal' => 100000,
                'hpp_snapshot' => 50000,
            ]
        );

        // OrderFinancial
        OrderFinancial::firstOrCreate(
            ['order_id' => $order->id],
            [
                'product_amount' => 100000,
                'shipping_paid_by_buyer' => 10000,
                'admin_fee' => 2000,
                'total_payment' => 110000,
                'total_income' => 108000,
                'settlement_amount' => 108000,
            ]
        );
    }
}
