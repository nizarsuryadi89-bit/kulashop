<?php

use App\Models\Product;
use App\Models\ProductPurchasePriceHistory;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('purchase reports require authentication', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['purchase.history', 'purchase.report']);

test('viewers cannot access purchase reports', function (string $routeName) {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)->get(route($routeName))->assertRedirect(route('dashboard'));
})->with(['purchase.history', 'purchase.report']);

test('owner can view and search purchase price history', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $product = Product::create(['sku' => 'DEMO-001', 'product_name' => 'Produk Demo']);
    ProductPurchasePriceHistory::create([
        'product_id' => $product->id,
        'purchase_price' => 50000,
        'qty' => 2,
    ]);

    $this->actingAs($owner)->get(route('purchase.history', ['search' => 'DEMO-001']))
        ->assertOk()
        ->assertSee('Produk Demo')
        ->assertSee('50.000');

    $this->get(route('purchase.history', ['search' => 'MISSING-SKU']))
        ->assertOk()
        ->assertDontSee('Produk Demo')
        ->assertSee('Belum ada histori harga beli.');
});

test('owner can view an empty annual purchase report', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)->get(route('purchase.report'))
        ->assertOk()
        ->assertViewHas('summary', [
            'total_orders' => 0,
            'total_amount' => 0,
            'total_paid' => 0,
            'total_outstanding' => 0,
        ]);
});

test('annual purchase report totals exclude cancelled orders and other years', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $supplier = Supplier::create(['supplier_code' => 'SUP-DEMO', 'name' => 'Supplier Demo']);
    $warehouse = Warehouse::create(['code' => 'WH-DEMO', 'name' => 'Gudang Demo']);

    foreach ([
        ['po_number' => 'PO-001', 'order_date' => '2026-01-10', 'status' => 'ordered', 'grand_total' => 100000, 'paid_amount' => 40000],
        ['po_number' => 'PO-002', 'order_date' => '2026-02-10', 'status' => 'received', 'grand_total' => 200000, 'paid_amount' => 200000],
        ['po_number' => 'PO-003', 'order_date' => '2026-01-15', 'status' => 'cancelled', 'grand_total' => 500000, 'paid_amount' => 0],
        ['po_number' => 'PO-004', 'order_date' => '2025-01-10', 'status' => 'ordered', 'grand_total' => 700000, 'paid_amount' => 0],
    ] as $attributes) {
        PurchaseOrder::create(array_merge($attributes, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
        ]));
    }

    $response = $this->actingAs($owner)->get(route('purchase.report', ['year' => 2026]));

    $response->assertOk()
        ->assertViewHas('availableYears', [2026, 2025])
        ->assertViewHas('summary', [
            'total_orders' => 2,
            'total_amount' => 300000,
            'total_paid' => 240000,
            'total_outstanding' => 60000,
        ]);

    $reportData = $response->viewData('reportData');
    expect($reportData)->toHaveCount(12)
        ->and($reportData[1]['total_outstanding'])->toEqual(60000)
        ->and($reportData[2]['total_amount'])->toEqual(200000)
        ->and($reportData[3]['total_orders'])->toEqual(0);
});
