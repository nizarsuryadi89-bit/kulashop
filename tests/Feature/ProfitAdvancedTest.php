<?php

use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ProfitService;
use App\Services\ShopeeOrderImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    \App\Models\Marketplace::forceCreate(['id' => 1, 'code' => 'SHP', 'name' => 'Shopee']);
    \App\Models\MarketplaceAccount::forceCreate(['id' => 1, 'marketplace_id' => 1, 'account_name' => 'Store 1', 'account_code' => 'ST1']);
    \Illuminate\Support\Facades\DB::table('customers')->insertOrIgnore(['id' => 1, 'customer_name' => 'John Doe', 'marketplace_account_id' => 1]);
    $this->profitService = app(ProfitService::class);
});

test('historical hpp does not change when product hpp changes', function () {
    $product = Product::forceCreate([
        'sku' => 'TEST-01',
        'product_name' => 'Produk A',
        'hpp' => 100000,
        'status' => 'active',
        'stock' => 10
    ]);

    // Order snapshot created with HPP 100000
    $order = new Order();
    $order->id = 1;
    $order->hpp_status = 'Complete';
    $order->income_status = 'Complete';

    $item = new OrderItem();
    $item->product_id = $product->id;
    $item->hpp_snapshot = 100000;
    $item->qty = 1;
    $item->returned_qty = 0;
    $order->setRelation('items', collect([$item]));

    $financial = new OrderFinancial();
    $financial->total_income = 150000;
    $order->setRelation('financial', $financial);

    $oldProfit = $this->profitService->getOrderProfit($order);
    expect($oldProfit)->toBe(50000.0);

    // Product HPP changed to 120000
    $product->update(['hpp' => 120000]);

    // Snapshot in order item should remain unchanged
    // Profit should still use 100000
    $newProfit = $this->profitService->getOrderProfit($order);
    expect((float) $item->hpp_snapshot)->toBe(100000.0);
    expect($newProfit)->toBe(50000.0);
});

test('import repeat does not duplicate order financial and hpp', function () {
    // Just simulating the DB layer's upsert functionality used in imports
    Order::upsert([
        [
            'external_order_id' => 'ORD-123',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
            'total_payment' => 200000,
            'marketplace_account_id' => 1,
            'customer_id' => 1
        ]
    ], ['marketplace_account_id', 'external_order_id']);
    
    $order = Order::where('external_order_id', 'ORD-123')->first();

    OrderFinancial::upsert([
        [
            'order_id' => $order->id,
            'total_income' => 180000
        ]
    ], ['order_id']);

    OrderItem::upsert([
        [
            'order_id' => $order->id,
            'product_name' => 'Prod A',
            'variant_name' => 'Var A',
            'qty' => 1,
            'hpp_snapshot' => 100000
        ]
    ], ['order_id', 'product_name', 'variant_name']);

    // Import 1 counts
    expect(Order::count())->toBe(1);
    expect(OrderFinancial::count())->toBe(1);
    expect(OrderItem::count())->toBe(1);

    // Import again exactly the same
    Order::upsert([
        [
            'external_order_id' => 'ORD-123',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
            'total_payment' => 200000,
            'marketplace_account_id' => 1,
            'customer_id' => 1
        ]
    ], ['marketplace_account_id', 'external_order_id']);
    
    $order = Order::where('external_order_id', 'ORD-123')->first();

    OrderFinancial::upsert([
        [
            'order_id' => $order->id,
            'total_income' => 180000
        ]
    ], ['order_id']);

    OrderItem::upsert([
        [
            'order_id' => $order->id,
            'product_name' => 'Prod A',
            'variant_name' => 'Var A',
            'qty' => 1,
            'hpp_snapshot' => 100000
        ]
    ], ['order_id', 'product_name', 'variant_name']);

    // Import 2 counts should be the same
    expect(Order::count())->toBe(1);
    expect(OrderFinancial::count())->toBe(1);
    expect(OrderItem::count())->toBe(1);
});

test('multi item calculates correctly', function () {
    $order = new Order();
    $order->hpp_status = 'Complete';
    $order->income_status = 'Complete';

    $itemA = new OrderItem();
    $itemA->qty = 2;
    $itemA->returned_qty = 0;
    $itemA->hpp_snapshot = 100000;

    $itemB = new OrderItem();
    $itemB->qty = 1;
    $itemB->returned_qty = 0;
    $itemB->hpp_snapshot = 50000;

    $order->setRelation('items', collect([$itemA, $itemB]));

    $financial = new OrderFinancial();
    $financial->total_income = 400000;
    $order->setRelation('financial', $financial);

    $profit = $this->profitService->getOrderProfit($order);
    $margin = $this->profitService->getOrderMargin($order);

    expect($order->total_hpp)->toBe(250000.0);
    expect($profit)->toBe(150000.0);
    expect(round($margin, 1))->toBe(37.5);
});

test('product profit aggregation calculates correctly', function () {
    $productA = Product::forceCreate([
        'sku' => 'A1', 'product_name' => 'Product A', 'hpp' => 600000, 'status' => 'active'
    ]);
    
    $productB = Product::forceCreate([
        'sku' => 'B1', 'product_name' => 'Product B', 'hpp' => 200000, 'status' => 'active'
    ]);

    $order1 = Order::forceCreate([
        'external_order_id' => '1', 'marketplace_account_id' => 1, 'order_status' => 'COMPLETED',
        'order_date' => now(), 'total_payment' => 1000000, 'customer_id' => 1
    ]);
    $order2 = Order::forceCreate([
        'external_order_id' => '2', 'marketplace_account_id' => 1, 'order_status' => 'COMPLETED',
        'order_date' => now(), 'total_payment' => 500000, 'customer_id' => 1
    ]);

    OrderFinancial::forceCreate(['order_id' => $order1->id, 'total_income' => 1000000]);
    OrderFinancial::forceCreate(['order_id' => $order2->id, 'total_income' => 500000]);

    OrderItem::forceCreate([
        'order_id' => $order1->id, 'product_id' => $productA->id,
        'product_name' => 'Product A', 'variant_name' => '', 'qty' => 1, 'hpp_snapshot' => 600000,
        'subtotal' => 1000000
    ]);
    OrderItem::forceCreate([
        'order_id' => $order2->id, 'product_id' => $productB->id,
        'product_name' => 'Product B', 'variant_name' => '', 'qty' => 1, 'hpp_snapshot' => 200000,
        'subtotal' => 500000
    ]);

    $productProfit = $this->profitService->getProductProfit();
    
    $profitA = collect($productProfit['by_profit'])->firstWhere('name', 'Product A');
    $profitB = collect($productProfit['by_profit'])->firstWhere('name', 'Product B');

    expect((float) $profitA['income'])->toBe(1000000.0);
    expect((float) $profitA['hpp'])->toBe(600000.0);
    expect((float) $profitA['gross_profit'])->toBe(400000.0);

    expect((float) $profitB['income'])->toBe(500000.0);
    expect((float) $profitB['hpp'])->toBe(200000.0);
    expect((float) $profitB['gross_profit'])->toBe(300000.0);
});
