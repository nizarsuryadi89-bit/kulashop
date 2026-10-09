<?php

use App\Imports\ShopeeOrderImport;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ShopeeOrderImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup basic data
    $this->user = User::factory()->create();

    $this->shopeeMarketplace = Marketplace::create([
        'name' => 'Shopee',
        'code' => 'shopee',
    ]);

    // SQLite dummy marketplace to satisfy the old foreign key that couldn't be dropped
    Marketplace::create([
        'name' => 'Dummy for sqlite FK',
        'code' => 'dummy',
    ]);

    $this->account = MarketplaceAccount::create([
        'marketplace_id' => $this->shopeeMarketplace->id,
        'account_name' => 'Shopee Store 1',
        'status' => 'active',
    ]);

    $this->account2 = MarketplaceAccount::create([
        'marketplace_id' => $this->shopeeMarketplace->id,
        'account_name' => 'Shopee Store 2',
        'status' => 'active',
    ]);

    $this->product = Product::create([
        'product_name' => 'Test Product',
        'sku' => 'SKU-001',
        'selling_price' => 100000,
        'stock' => 10,
        'minimum_stock' => 5,
    ]);
});

function createShopeeCsvContent(array $rows, bool $validHeader = true)
{
    $header = $validHeader ? [
        'No. Pesanan', 'Status Pesanan', 'Waktu Pesanan Selesai', 'Waktu Pembatalan',
        'Total Pembayaran', 'Ongkos Kirim Dibayar oleh Pembeli', 'Subtotal Pesanan',
        'Total Diskon', 'Username (Pembeli)', 'No. Telepon', 'Nama Penerima',
        'Alamat Pengiriman', 'Provinsi', 'Kota/Kabupaten',
        'Kecamatan', 'Nama Produk', 'Nomor Referensi SKU', 'Harga Awal', 'Jumlah', 'Harga Setelah Diskon',
        'Total Harga Produk', 'Catatan dari Pembeli', 'No. Resi', 'Opsi Pengiriman', 'Metode Pembayaran', 'Waktu Pesanan Dibuat', 'Waktu Pembayaran Dilakukan', 'Status Pembatalan/ Pengembalian',
    ] : ['Header Salah 1', 'Header Salah 2'];

    $fp = fopen('php://temp', 'r+');
    fputcsv($fp, $header);
    foreach ($rows as $row) {
        $padded = array_pad($row, count($header), '');
        fputcsv($fp, $padded);
    }
    rewind($fp);
    $csv = stream_get_contents($fp);
    fclose($fp);

    return $csv;
}

function runImport($csvContent, $accountId)
{
    Storage::fake('private');
    $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);
    $path = $file->storeAs('imports', 'test_import.csv', 'private');

    $batch = ImportBatch::create([
        'marketplace_account_id' => $accountId,
        'import_type' => 'shopee_order',
        'file_name' => 'test_import.csv',
        'file_path' => $path,
        'uploaded_by' => auth()->id() ?? User::first()->id ?? 1,
        'status' => 'processing',
    ]);

    Excel::import(new ShopeeOrderImport($batch), $path, 'private');

    if (ImportError::count() > 0) {
        dump(ImportError::all()->toArray());
    }

    return $batch;
}

test('TEST 1 - Single Order', function () {
    $rows = [
        ['ORD1', 'Selesai', '2026-09-01 10:00', '', 'Rp 100.000', '0', '100000', '0', 'user1', '0812', 'Penerima 1', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk 1', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI123', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];
    $csv = createShopeeCsvContent($rows);

    runImport($csv, $this->account->id);

    expect(Order::count())->toBe(1);
    expect(OrderItem::count())->toBe(1);

    $order = Order::first();
    expect($order->external_order_id)->toBe('ORD1');
    expect((float) $order->total_payment)->toBe(100000.0);
});

test('TEST 2 - Multiple SKU (1 order 3 items)', function () {
    $rows = [
        ['ORD2', 'Selesai', '2026-09-01 10:00', '', '300000', '0', '300000', '0', 'user2', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk A', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI2', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
        ['ORD2', 'Selesai', '2026-09-01 10:00', '', '300000', '0', '300000', '0', 'user2', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk B', 'SKU-002', '100000', '1', '100000', '100000', '', 'RESI2', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
        ['ORD2', 'Selesai', '2026-09-01 10:00', '', '300000', '0', '300000', '0', 'user2', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk C', 'SKU-003', '100000', '1', '100000', '100000', '', 'RESI2', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];

    runImport(createShopeeCsvContent($rows), $this->account->id);

    expect(Order::count())->toBe(1);
    expect(OrderItem::count())->toBe(3);
});

test('TEST 3 - Duplicate File', function () {
    $rows = [
        ['ORD3', 'Selesai', '2026-09-01 10:00', '', '100000', '0', '100000', '0', 'user3', '0812', 'Penerima 3', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI3', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];
    $csv = createShopeeCsvContent($rows);

    // First Import
    runImport($csv, $this->account->id);
    expect(Order::count())->toBe(1);
    expect(OrderItem::count())->toBe(1);

    // Second Import
    runImport($csv, $this->account->id);
    expect(Order::count())->toBe(1);
    expect(OrderItem::count())->toBe(1);
});

test('TEST 4 - Update Status', function () {
    $rowsFirst = [
        ['ORD4', 'Diproses', '-', '', '100000', '0', '100000', '0', 'user4', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI4', 'Reguler', 'Transfer', '2026-09-01 09:00', '-', ''],
    ];
    runImport(createShopeeCsvContent($rowsFirst), $this->account->id);

    $order = Order::where('external_order_id', 'ORD4')->first();
    expect($order->order_substatus)->toBe('Diproses'); // mapped status internally might be 'processing', order_substatus stores exactly as from shopee
    expect($order->completed_at)->toBeNull();

    $rowsSecond = [
        ['ORD4', 'Selesai', '2026-09-02 15:00', '', '100000', '0', '100000', '0', 'user4', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI4', 'Reguler', 'Transfer', '2026-09-01 09:00', '-', ''],
    ];
    runImport(createShopeeCsvContent($rowsSecond), $this->account->id);

    expect(Order::count())->toBe(1);

    $order->refresh();
    expect($order->order_substatus)->toBe('Selesai');
    expect($order->completed_at)->not->toBeNull();
});

test('TEST 5 - Customer', function () {
    $rows = [
        ['ORD5A', 'Selesai', '2026-09-01 10:00', '', '100000', '0', '100000', '0', 'john_doe', '081234', 'John Doe', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI5A', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
        ['ORD5B', 'Selesai', '2026-09-02 10:00', '', '100000', '0', '100000', '0', 'john_doe', '081234', 'John Doe', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI5B', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];

    runImport(createShopeeCsvContent($rows), $this->account->id);

    expect(Order::count())->toBe(2);
    // Customer identifier for shopee is marketplace_account_id + username
    expect(Customer::where('marketplace_account_id', $this->account->id)->where('username', 'john_doe')->count())->toBe(1);
});

test('TEST 6 - Unmapped SKU', function () {
    $rows = [
        ['ORD6', 'Selesai', '2026-09-01 10:00', '', '100000', '0', '100000', '0', 'user6', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk Baru', 'SKU-UNKNOWN', '100000', '1', '100000', '100000', '', 'RESI6', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];

    runImport(createShopeeCsvContent($rows), $this->account->id);

    expect(Order::count())->toBe(1);

    $item = OrderItem::first();
    expect($item->product_id)->toBeNull();
    expect($item->marketplace_sku)->toBe('SKU-UNKNOWN');
});

test('TEST 7 - Invalid Header', function () {
    Storage::fake('private');
    $csvContent = createShopeeCsvContent([['Data 1', 'Data 2']], false); // validHeader = false

    $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);
    $path = $file->storeAs('imports', 'invalid.csv', 'private');

    $service = app(ShopeeOrderImportService::class);

    expect(fn () => $service->processPreview($path, 'invalid.csv', $this->account->id))
        ->toThrow(Exception::class);

    expect(Order::count())->toBe(0);
});

test('TEST 8 - Multi Marketplace Account', function () {
    $rows = [
        ['ORD8', 'Selesai', '2026-09-01 10:00', '', '100000', '0', '100000', '0', 'user8', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk', 'SKU-001', '100000', '1', '100000', '100000', '', 'RESI8', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];
    $csv = createShopeeCsvContent($rows);

    // Import for account 1
    runImport($csv, $this->account->id);

    // Import for account 2 with SAME external_order_id
    runImport($csv, $this->account2->id);

    // Should create 2 distinct orders because marketplace_account_id is different
    expect(Order::count())->toBe(2);

    $order1 = Order::where('marketplace_account_id', $this->account->id)->first();
    $order2 = Order::where('marketplace_account_id', $this->account2->id)->first();

    expect($order1->external_order_id)->toBe('ORD8');
    expect($order2->external_order_id)->toBe('ORD8');
    expect($order1->id)->not->toBe($order2->id);
});

test('TEST 9 - Mapped SKU Resolution', function () {
    // Setup mapping for SKU-MAPPED -> Test Product (SKU-001)
    \App\Models\ProductMarketplace::create([
        'product_id' => $this->product->id,
        'marketplace_account_id' => $this->account->id,
        'marketplace_sku' => 'SKU-MAPPED',
        'is_active' => true,
    ]);

    $rows = [
        ['ORD9', 'Selesai', '2026-09-01 10:00', '', '100000', '0', '100000', '0', 'user9', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk Mapped', 'SKU-MAPPED', '100000', '1', '100000', '100000', '', 'RESI9', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];

    runImport(createShopeeCsvContent($rows), $this->account->id);

    $item = OrderItem::whereHas('order', fn($q) => $q->where('external_order_id', 'ORD9'))->first();
    
    // Harus berhasil menemukan product_id
    expect($item->product_id)->toBe($this->product->id);
    // HPP Snapshot takes from product
    expect((float) $item->hpp_snapshot)->toBe((float) $this->product->hpp);
});

test('TEST 10 - Historical Data HPP Protection', function () {
    \App\Models\ProductMarketplace::create([
        'product_id' => $this->product->id,
        'marketplace_account_id' => $this->account->id,
        'marketplace_sku' => 'SKU-MAPPED-2',
        'is_active' => true,
    ]);

    $rows = [
        ['ORD10', 'Selesai', '2026-09-01 10:00', '', '100000', '0', '100000', '0', 'user10', '0812', 'Penerima', 'Alamat', 'Prov', 'Kota', 'Kec', 'Produk Historis', 'SKU-MAPPED-2', '100000', '1', '100000', '100000', '', 'RESI10', 'Reguler', 'Transfer', '2026-09-01 09:00', '2026-09-01 09:05', ''],
    ];

    runImport(createShopeeCsvContent($rows), $this->account->id);

    $item = OrderItem::whereHas('order', fn($q) => $q->where('external_order_id', 'ORD10'))->first();
    $originalHpp = (float) $this->product->hpp;
    expect((float) $item->hpp_snapshot)->toBe($originalHpp);

    // Mengubah Master Data HPP
    $this->product->update(['hpp' => 75000]);
    
    // Re-import pesanan yang sama
    runImport(createShopeeCsvContent($rows), $this->account->id);

    // HPP Snapshot tidak boleh berubah mengikuti HPP baru karena data sudah ada
    $item->refresh();
    expect((float) $item->hpp_snapshot)->toBe($originalHpp);
});
