<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('hpp changes do not affect existing order items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $marketplace = \App\Models\Marketplace::create(['name' => 'Shopee', 'code' => 'shopee']);
    $marketplaceAccount = \App\Models\MarketplaceAccount::create(['marketplace_id' => $marketplace->id, 'account_name' => 'Shopee 1', 'is_active' => true]);
    $customer = \App\Models\Customer::create(['customer_name' => 'Test Customer', 'phone' => '08123456789']);

    // 1. 01 Januari: Create product with HPP 100.000
    $product = Product::create([
        'sku' => 'TEST-01',
        'product_name' => 'Product A',
        'hpp' => 100000,
        'selling_price' => 150000,
    ]);

    // 2. 10 Januari: Order A
    $orderA = Order::create([
        'marketplace_account_id' => $marketplaceAccount->id,
        'customer_id' => $customer->id,
        'external_order_id' => 'ORD-01',
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    $orderItemA = OrderItem::create([
        'order_id' => $orderA->id,
        'product_id' => $product->id,
        'hpp_snapshot' => $product->hpp,
        'qty' => 1,
        'subtotal' => 150000,
        'selling_price' => 150000,
        'original_price' => 150000,
        'marketplace_item_id' => 'ITEM-01',
        'item_name' => 'Product A'
    ]);

    expect((float) $orderItemA->hpp_snapshot)->toEqual(100000.0);

    // 3. 01 Februari: Update Product HPP to 120.000
    $product->update(['hpp' => 120000]);

    // 4. 10 Februari: Order B
    $orderB = Order::create([
        'marketplace_account_id' => $marketplaceAccount->id,
        'customer_id' => $customer->id,
        'external_order_id' => 'ORD-02',
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    $orderItemB = OrderItem::create([
        'order_id' => $orderB->id,
        'product_id' => $product->id,
        'hpp_snapshot' => $product->hpp,
        'qty' => 1,
        'subtotal' => 150000,
        'selling_price' => 150000,
        'original_price' => 150000,
        'marketplace_item_id' => 'ITEM-02',
        'item_name' => 'Product A'
    ]);

    // 5. Assertions:
    
    // Order A MUST still be 100.000
    $orderItemA->refresh();
    expect((float) $orderItemA->hpp_snapshot)->toEqual(100000.0);
    
    // Order B MUST be 120.000
    expect((float) $orderItemB->hpp_snapshot)->toEqual(120000.0);
    
    // Order A total HPP is 100.000
    expect((float) $orderA->total_hpp)->toEqual(100000.0);
    
    // Order B total HPP is 120.000
    expect((float) $orderB->total_hpp)->toEqual(120000.0);
});

test('order returns null total hpp and incomplete status if any item has null hpp', function () {
    $marketplace = \App\Models\Marketplace::create(['name' => 'Shopee', 'code' => 'shopee']);
    $marketplaceAccount = \App\Models\MarketplaceAccount::create(['marketplace_id' => $marketplace->id, 'account_name' => 'Shopee 1', 'is_active' => true]);
    $customer = \App\Models\Customer::create(['customer_name' => 'Test Customer', 'phone' => '08123456789']);

    $productA = Product::create([
        'sku' => 'TEST-A',
        'product_name' => 'Product A',
        'hpp' => 100000,
        'selling_price' => 150000,
    ]);

    $productB = Product::create([
        'sku' => 'TEST-B',
        'product_name' => 'Product B',
        'hpp' => 0, // Not used because we simulate missing HPP snapshot below
        'selling_price' => 200000,
    ]);

    $order = Order::create([
        'marketplace_account_id' => $marketplaceAccount->id,
        'customer_id' => $customer->id,
        'external_order_id' => 'ORD-NULL-TEST',
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    // Item with HPP 100.000
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $productA->id,
        'hpp_snapshot' => 100000,
        'qty' => 1,
        'subtotal' => 150000,
        'selling_price' => 150000,
        'original_price' => 150000,
        'marketplace_item_id' => 'ITEM-A',
        'item_name' => 'Product A'
    ]);

    // Item with NULL HPP Snapshot
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $productB->id,
        'hpp_snapshot' => null,
        'qty' => 1,
        'subtotal' => 200000,
        'selling_price' => 200000,
        'original_price' => 200000,
        'marketplace_item_id' => 'ITEM-B',
        'item_name' => 'Product B'
    ]);

    $order->refresh();

    // Pastikan status = Incomplete
    expect($order->hpp_status)->toEqual('Incomplete');
    
    // Pastikan Total HPP = null (bukan 100.000)
    expect($order->total_hpp)->toBeNull();
});
