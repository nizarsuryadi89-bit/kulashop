<?php

use App\Models\Product;
use App\Models\ProductHppHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);
});

test('it updates product hpp and creates history record', function () {
    $product = Product::create([
        'sku' => 'TEST-SKU-1',
        'product_name' => 'Test Product',
        'hpp' => 100000,
    ]);

    $response = $this->actingAs($this->admin)->put(route('product.update-hpp', $product->id), [
        'new_hpp' => 120000,
        'reason' => 'Harga supplier naik',
    ]);

    $response->assertSessionHas('success');
    
    // Pastikan products.hpp = 120000
    expect($product->fresh()->hpp)->toEqual(120000);

    // Pastikan history terbuat
    $history = ProductHppHistory::where('product_id', $product->id)->latest()->first();
    expect($history)->not->toBeNull();
    expect($history->old_hpp)->toEqual(100000);
    expect($history->new_hpp)->toEqual(120000);
    expect($history->reason)->toEqual('Harga supplier naik');
    expect($history->changed_by)->toEqual($this->admin->id);
});

test('it does not create history if hpp is unchanged', function () {
    $product = Product::create([
        'sku' => 'TEST-SKU-2',
        'product_name' => 'Test Product 2',
        'hpp' => 100000,
    ]);

    $response = $this->actingAs($this->admin)->put(route('product.update-hpp', $product->id), [
        'new_hpp' => 100000,
        'reason' => 'Tidak ada perubahan',
    ]);

    $response->assertSessionHas('info', 'HPP tidak berubah.');
    
    // Pastikan products.hpp tetap 100000
    expect($product->fresh()->hpp)->toEqual(100000);

    // Pastikan tidak membuat history baru
    $historyCount = ProductHppHistory::where('product_id', $product->id)->count();
    expect($historyCount)->toEqual(0);
});
