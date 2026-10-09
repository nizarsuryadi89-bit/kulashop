<?php

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to view dashboard', function () {
    $response = $this->get('/dashboard');
    
    $response->assertRedirect('/login');
});

it('can view dashboard when authenticated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
});

it('can filter dashboard by period', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard?period=last_month');

    $response->assertStatus(200);
    // Since metrics are passed to view, we just ensure it doesn't crash
});

it('can filter dashboard by marketplace account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard?marketplace_account_id=1');

    $response->assertStatus(200);
});

it('calculates dashboard metrics without crashing when data is present', function () {
    $user = User::factory()->create();
    
    $marketplace = Marketplace::create([
        'name' => 'Shopee',
        'code' => 'shopee'
    ]);
    
    $account = MarketplaceAccount::create([
        'marketplace_id' => $marketplace->id,
        'account_name' => 'My Shop',
        'is_active' => true
    ]);
    
    $customer = Customer::create([
        'customer_name' => 'John Doe',
        'marketplace_account_id' => $account->id
    ]);
    
    $product = Product::create([
        'product_name' => 'Test Product',
        'current_stock' => 10,
        'sku' => 'TEST-01',
        'is_active' => true
    ]);

    $order = Order::create([
        'external_order_id' => 'ORD-001',
        'marketplace_account_id' => $account->id,
        'customer_id' => $customer->id,
        'total_payment' => 50000,
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'qty' => 2,
        'price' => 25000,
        'product_name' => 'Test Product',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('50.000'); // Total Penjualan
    $response->assertSee('Test Product');
});
