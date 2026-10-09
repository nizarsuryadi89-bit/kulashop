<?php

use App\Models\Customer;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->viewer = User::factory()->create([
        'role' => 'viewer',
    ]);

    // Create default warehouse
    $this->warehouse = Warehouse::create([
        'name' => 'Gudang Utama',
        'code' => 'GDG-UTAMA',
        'address' => 'Jl. Penggilingan No. 10',
        'is_default' => true,
        'status' => 'active',
    ]);

    // Secondary warehouse
    $this->branchWarehouse = Warehouse::create([
        'name' => 'Gudang Cabang',
        'code' => 'GDG-CABANG',
        'address' => 'Jl. Sudirman No. 5',
        'is_default' => false,
        'status' => 'active',
    ]);

    // Test product
    $this->product = Product::create([
        'sku' => 'SKU-INV-001',
        'product_name' => 'Kemeja Katun Pria',
        'selling_price' => 150000,
        'hpp' => 80000,
        'stock' => 0,
        'minimum_stock' => 10,
        'status' => 'active',
    ]);

    // Seed a warehouse_stock so SKU appears in the stock table view
    \App\Models\WarehouseStock::firstOrCreate(
        ['warehouse_id' => $this->warehouse->id, 'product_id' => $this->product->id],
        ['qty' => 0]
    );
});

test('admin can view inventory overview and warehouse list', function () {
    $response = $this->actingAs($this->admin)->get(route('inventory.index'));
    $response->assertOk();
    $response->assertSee('Tabel Stok Produk');
    $response->assertSee('SKU-INV-001');

    $responseWh = $this->actingAs($this->admin)->get(route('warehouses.index'));
    $responseWh->assertOk();
    $responseWh->assertSee('Gudang Utama');
    $responseWh->assertSee('Gudang Cabang');
});

test('viewer role cannot modify inventory or warehouses', function () {
    $response = $this->actingAs($this->viewer)->get(route('inventory.index'));
    $response->assertRedirect(route('dashboard'));

    $responseWh = $this->actingAs($this->viewer)->get(route('warehouses.index'));
    $responseWh->assertRedirect(route('dashboard'));
});

test('admin can record stock in and it updates warehouse and aggregate product stock', function () {
    $response = $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reference_type' => 'manual_in',
        'notes' => 'Penerimaan PO-2026-001',
    ]);

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');

    // Check warehouse stock
    $whStock = WarehouseStock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($whStock)->not->toBeNull()
        ->and($whStock->qty)->toEqual(50);

    // Check aggregate stock on Product model
    $this->product->refresh();
    expect($this->product->stock)->toEqual(50);

    // Check stock movement entry
    $movement = StockMovement::where('product_id', $this->product->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe('in')
        ->and($movement->qty)->toEqual(50)
        ->and($movement->stock_before)->toEqual(0)
        ->and($movement->stock_after)->toEqual(50)
        ->and($movement->note)->toBe('Penerimaan PO-2026-001');
});

test('admin can record stock out and fails if quantity exceeds available stock', function () {
    // Initially product has 10 stock in warehouse
    WarehouseStock::firstOrCreate(
        ['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id],
        ['qty' => 10]
    )->update(['qty' => 10]);
    $this->product->update(['stock' => 10]);

    // Try to take out 15 (exceeds 10)
    $response = $this->actingAs($this->admin)->from(route('inventory.stock-out'))->post(route('inventory.stock-out.store'), [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 15,
        'notes' => 'Sampel Berlebih',
    ]);

    $response->assertRedirect(route('inventory.stock-out'));
    $response->assertSessionHas('error');

    // Stock should remain unchanged
    $this->product->refresh();
    expect($this->product->stock)->toEqual(10);
});

test('admin can record stock out successfully when stock is sufficient', function () {
    // Setup 30 stock in warehouse
    $inventoryService = app(InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 30, 'Initial restock', 'manual_in', null, $this->admin->id);

    $response = $this->actingAs($this->admin)->post(route('inventory.stock-out.store'), [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'notes' => 'Sampel Promosi Endorse',
    ]);

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');

    $this->product->refresh();
    expect($this->product->stock)->toEqual(20);

    $whStock = WarehouseStock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($whStock->qty)->toEqual(20);
});

test('admin can adjust stock through stock opname', function () {
    $inventoryService = app(InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 20, 'Initial Stock');

    // Physical count found 18 pcs (difference: -2)
    $response = $this->actingAs($this->admin)->post(route('inventory.adjustment.store'), [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'actual_stock' => 18,
        'notes' => 'Opname Akhir Bulan: 2 pcs hilang/rusak',
    ]);

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHas('success');

    $this->product->refresh();
    expect($this->product->stock)->toEqual(18);

    $movement = StockMovement::where('product_id', $this->product->id)
        ->where('type', 'adjustment')
        ->first();
    expect($movement)->not->toBeNull()
        ->and($movement->qty)->toEqual(2)
        ->and($movement->stock_before)->toEqual(20)
        ->and($movement->stock_after)->toEqual(18);
});

test('stock card displays ledger records and low stock warning triggers', function () {
    $inventoryService = app(InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 5, 'Restock kecil', 'manual_in', null, $this->admin->id);

    // Product stock is 5, minimum_stock is 10 -> should be in low stock list
    $responseLow = $this->actingAs($this->admin)->get(route('inventory.low-stock'));
    $responseLow->assertOk();
    $responseLow->assertSee('SKU-INV-001');
    $responseLow->assertSee('Kemeja Katun Pria');

    // Stock card view
    $responseCard = $this->actingAs($this->admin)->get(route('inventory.stock-card', $this->product->id));
    $responseCard->assertOk();
    $responseCard->assertSee('Kartu Stok');
    $responseCard->assertSee('Restock kecil');
});

test('warehouse management enforces single default warehouse and prevents deletion with active stock', function () {
    // Creating warehouse via store
    $response = $this->actingAs($this->admin)->post(route('warehouses.store'), [
        'name' => 'Gudang Surabaya',
        'code' => 'GDG-SBY',
        'address' => 'Rungkut Industri',
        'is_default' => '1',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('warehouses.index'));

    $newWh = Warehouse::where('code', 'GDG-SBY')->first();
    expect($newWh->is_default)->toBeTrue();

    // Previous default should be demoted
    $this->warehouse->refresh();
    expect($this->warehouse->is_default)->toBeFalse();

    // Add stock to new warehouse
    WarehouseStock::create([
        'warehouse_id' => $newWh->id,
        'product_id' => $this->product->id,
        'qty' => 15,
    ]);

    // Deleting warehouse with active stock should be prevented
    $responseDelete = $this->actingAs($this->admin)
        ->from(route('warehouses.index'))
        ->delete(route('warehouses.destroy', $newWh->id));
    $responseDelete->assertRedirect(route('warehouses.index'));
    $responseDelete->assertSessionHas('error');
    expect(Warehouse::where('id', $newWh->id)->exists())->toBeTrue();
});

test('syncOrderStockStatus deducts stock for PAID/SHIPPED/COMPLETED orders (idempotent)', function () {
    $inventoryService = app(InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 100, 'Saldo Awal');

    $customer = Customer::create([
        'customer_name' => 'Budi Santoso',
        'customer_code' => 'CUST-TEST-001',
    ]);

    $mp = Marketplace::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);
    $account = MarketplaceAccount::create([
        'marketplace_id' => $mp->id,
        'account_name'   => 'Toko Shopee Official',
        'status'         => 'active',
    ]);

    $order = Order::create([
        'external_order_id'      => 'SHP-2026-TEST-001',
        'marketplace_account_id' => $account->id,
        'customer_id'            => $customer->id,
        'order_status'           => 'PAID', // Status yang memicu pemotongan
        'order_date'             => now(),
        'total_payment'          => 300000,
    ]);

    OrderItem::create([
        'order_id'     => $order->id,
        'product_id'   => $this->product->id,
        'product_name' => 'Kemeja Katun Pria',
        'qty'          => 3,
    ]);

    // Sync status → harus potong stok karena PAID
    $inventoryService->syncOrderStockStatus($order);

    $this->product->refresh();
    $order->refresh();
    expect($this->product->stock)->toEqual(97)
        ->and($order->stock_deducted)->toBeTrue();

    // Panggil kedua kali → idempotent, stok tidak berubah
    $inventoryService->syncOrderStockStatus($order);

    $this->product->refresh();
    expect($this->product->stock)->toEqual(97); // tetap 97, bukan 94

    // Pastikan hanya 1 movement untuk order ini
    $movementsCount = StockMovement::where('reference_type', 'order')
        ->where('reference_id', $order->id)
        ->count();
    expect($movementsCount)->toBe(1);
});

test('syncOrderStockStatus restores stock when order becomes CANCELLED', function () {
    $inventoryService = app(InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 50, 'Saldo Awal');

    $customer = Customer::create([
        'customer_name' => 'Dewi Rahayu',
        'customer_code' => 'CUST-TEST-002',
    ]);

    $mp = Marketplace::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);
    $account = MarketplaceAccount::firstOrCreate(
        ['account_name' => 'Toko Shopee Test Restore'],
        ['marketplace_id' => $mp->id, 'status' => 'active']
    );

    // Order awal = PAID → stok dipotong
    $order = Order::create([
        'external_order_id'      => 'SHP-2026-TEST-002',
        'marketplace_account_id' => $account->id,
        'customer_id'            => $customer->id,
        'order_status'           => 'PAID',
        'order_date'             => now(),
        'total_payment'          => 200000,
    ]);

    OrderItem::create([
        'order_id'     => $order->id,
        'product_id'   => $this->product->id,
        'product_name' => 'Kemeja Katun Pria',
        'qty'          => 5,
    ]);

    // Sync PAID → potong 5 unit
    $inventoryService->syncOrderStockStatus($order);
    $this->product->refresh();
    $order->refresh();
    expect($this->product->stock)->toEqual(45)
        ->and($order->stock_deducted)->toBeTrue();

    // Order berubah jadi CANCELLED → kembalikan stok
    $order->update(['order_status' => 'CANCELLED']);
    $order->refresh();

    $inventoryService->syncOrderStockStatus($order);
    $this->product->refresh();
    $order->refresh();

    expect($this->product->stock)->toEqual(50)      // kembali ke 50
        ->and($order->stock_deducted)->toBeFalse();

    // Harus ada movement type=in reference_type=order_return
    $restoreMovement = StockMovement::where('reference_type', 'order_return')
        ->where('reference_id', $order->id)
        ->first();
    expect($restoreMovement)->not->toBeNull()
        ->and($restoreMovement->qty)->toEqual(5);
});

test('syncOrderStockStatus does NOT deduct for PENDING orders', function () {
    $inventoryService = app(InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 30, 'Saldo Awal');

    $customer = Customer::create([
        'customer_name' => 'Eko Susanto',
        'customer_code' => 'CUST-TEST-003',
    ]);

    $mp = Marketplace::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);
    $account = MarketplaceAccount::firstOrCreate(
        ['account_name' => 'Toko Shopee Test Pending'],
        ['marketplace_id' => $mp->id, 'status' => 'active']
    );

    $order = Order::create([
        'external_order_id'      => 'SHP-2026-TEST-003',
        'marketplace_account_id' => $account->id,
        'customer_id'            => $customer->id,
        'order_status'           => 'PENDING',
        'order_date'             => now(),
        'total_payment'          => 150000,
    ]);

    OrderItem::create([
        'order_id'     => $order->id,
        'product_id'   => $this->product->id,
        'product_name' => 'Kemeja Katun Pria',
        'qty'          => 2,
    ]);

    // Sync PENDING → tidak ada aksi stok
    $inventoryService->syncOrderStockStatus($order);

    $this->product->refresh();
    $order->refresh();
    expect($this->product->stock)->toEqual(30)      // tidak berubah
        ->and($order->stock_deducted)->toBeFalse(); // flag tetap false
});

test('it prevents negative stock and throws exception when stock is insufficient', function () {
    $inventoryService = app(\App\Services\Inventory\InventoryService::class);
    $inventoryService->increaseStock($this->product->id, $this->warehouse->id, 10, 'Saldo Awal');

    // Attempting to deduct more than available should throw an exception and rollback
    expect(fn () => $inventoryService->decreaseStock($this->product->id, $this->warehouse->id, 15, 'Deduct too much'))
        ->toThrow(\Exception::class, 'Stok tidak mencukupi');

    // Stock should remain unchanged
    $this->product->refresh();
    expect($this->product->stock)->toEqual(10);
});
