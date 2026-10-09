<?php

use App\Models\Customer;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ProfitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it calculates gross profit correctly', function () {
    $marketplace = Marketplace::create(['name' => 'Shopee', 'code' => 'shopee']);
    $account = MarketplaceAccount::create(['marketplace_id' => $marketplace->id, 'account_name' => 'Shopee 1', 'is_active' => true]);
    $customer = Customer::create(['customer_name' => 'Test', 'phone' => '123']);

    $product = Product::create([
        'sku' => 'SKU-TEST',
        'product_name' => 'Test',
        'hpp' => 50000,
    ]);

    $order = Order::create([
        'marketplace_account_id' => $account->id,
        'customer_id' => $customer->id,
        'external_order_id' => 'ORD-1',
        'total_payment' => 100000,
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'hpp_snapshot' => 50000,
        'qty' => 1,
        'subtotal' => 100000,
        'selling_price' => 100000,
        'original_price' => 100000,
        'marketplace_item_id' => 'ITEM-1',
        'item_name' => 'Test',
    ]);

    OrderFinancial::create([
        'order_id' => $order->id,
        'product_amount' => 100000,
        'total_income' => 90000, // 10k fee
    ]);

    $service = app(ProfitService::class);
    $summary = $service->getProfitSummary();

    // Sales = 100000
    // Income = 90000
    // HPP = 50000
    // Profit = 90000 - 50000 = 40000
    // Margin = (40000 / 90000) * 100 = 44.44%

    expect((float) $summary['total_payment'])->toEqual(100000.0);
    expect((float) $summary['total_income'])->toEqual(90000.0);
    expect((float) $summary['total_hpp'])->toEqual(50000.0);
    expect((float) $summary['gross_profit'])->toEqual(40000.0);
    expect(round($summary['gross_margin'], 2))->toEqual(44.44);
});

test('it ignores incomplete hpp orders', function () {
    $marketplace = Marketplace::create(['name' => 'Shopee', 'code' => 'shopee']);
    $account = MarketplaceAccount::create(['marketplace_id' => $marketplace->id, 'account_name' => 'Shopee 1', 'is_active' => true]);
    $customer = Customer::create(['customer_name' => 'Test', 'phone' => '123']);

    $order = Order::create([
        'marketplace_account_id' => $account->id,
        'customer_id' => $customer->id,
        'external_order_id' => 'ORD-2',
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'hpp_snapshot' => null, // INCOMPLETE
        'qty' => 1,
        'subtotal' => 100000,
        'selling_price' => 100000,
        'original_price' => 100000,
        'marketplace_item_id' => 'ITEM-2',
        'item_name' => 'Test',
    ]);

    OrderFinancial::create([
        'order_id' => $order->id,
        'product_amount' => 100000,
        'total_income' => 90000,
    ]);

    $service = app(ProfitService::class);
    $summary = $service->getProfitSummary();

    // Order excluded
    expect($summary['complete_orders_count'])->toEqual(0);
    expect($summary['incomplete_orders_count'])->toEqual(1);
    expect((float) $summary['total_payment'])->toEqual(0.0);

    $missing = $service->getOrdersMissingHpp();
    expect($missing->count())->toEqual(1);
});

test('it calculates product profit allocation correctly', function () {
    $marketplace = Marketplace::create(['name' => 'Shopee', 'code' => 'shopee']);
    $account = MarketplaceAccount::create(['marketplace_id' => $marketplace->id, 'account_name' => 'Shopee 1', 'is_active' => true]);
    $customer = Customer::create(['customer_name' => 'Test', 'phone' => '123']);

    $order = Order::create([
        'marketplace_account_id' => $account->id,
        'customer_id' => $customer->id,
        'external_order_id' => 'ORD-3',
        'order_status' => 'COMPLETED',
        'order_date' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'hpp_snapshot' => 50000,
        'qty' => 1,
        'subtotal' => 100000,
        'selling_price' => 100000,
        'original_price' => 100000,
        'marketplace_item_id' => 'ITEM-3A',
        'marketplace_sku' => 'SKU-A',
        'item_name' => 'Test A',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'hpp_snapshot' => 20000,
        'qty' => 1,
        'subtotal' => 50000,
        'selling_price' => 50000,
        'original_price' => 50000,
        'marketplace_item_id' => 'ITEM-3B',
        'marketplace_sku' => 'SKU-B',
        'item_name' => 'Test B',
    ]);

    // Total subtotal = 150000. Total HPP = 70000.
    // Income = 120000.
    OrderFinancial::create([
        'order_id' => $order->id,
        'product_amount' => 150000,
        'total_income' => 120000,
    ]);

    $service = app(ProfitService::class);
    $metrics = $service->getProductProfit();

    // SKU-A Income allocation: (100000 / 150000) * 120000 = 80000
    // SKU-A Profit = 80000 - 50000 = 30000

    // SKU-B Income allocation: (50000 / 150000) * 120000 = 40000
    // SKU-B Profit = 40000 - 20000 = 20000

    $top = $metrics['by_profit'];
    expect(count($top))->toEqual(2);

    // SKU-A should be first (30000 profit)
    expect($top[0]['sku'])->toEqual('SKU-A');
    expect((float) $top[0]['gross_profit'])->toEqual(30000.0);

    // SKU-B should be second (20000 profit)
    expect($top[1]['sku'])->toEqual('SKU-B');
    expect((float) $top[1]['gross_profit'])->toEqual(20000.0);
});
