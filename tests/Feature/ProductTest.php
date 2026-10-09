<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        
        $this->user = User::factory()->create([
            'role' => 'viewer',
        ]);
    }

    public function test_admin_can_create_product()
    {
        $response = $this->actingAs($this->admin)->post(route('product.store'), [
            'sku' => 'SKU-TEST-1',
            'product_name' => 'Baju Tes',
            'hpp' => 50000,
            'selling_price' => 100000,
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect(route('product.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-TEST-1',
            'product_name' => 'Baju Tes',
        ]);
    }
    
    public function test_non_admin_cannot_create_product()
    {
        $response = $this->actingAs($this->user)->post(route('product.store'), [
            'sku' => 'SKU-TEST-2',
            'product_name' => 'Baju Tes',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_create_product_rejects_duplicate_sku()
    {
        Product::create([
            'sku' => 'SKU-DUPE',
            'product_name' => 'Baju 1',
            'hpp' => 50000,
            'selling_price' => 100000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('product.store'), [
            'sku' => 'SKU-DUPE',
            'product_name' => 'Baju 2',
            'hpp' => 60000,
            'selling_price' => 120000,
        ]);

        $response->assertSessionHasErrors('sku');
        $this->assertEquals(1, Product::where('sku', 'SKU-DUPE')->count());
    }

    public function test_product_validation_hpp_and_price()
    {
        $response = $this->actingAs($this->admin)->post(route('product.store'), [
            'sku' => 'SKU-TEST-3',
            'product_name' => 'Baju',
            'hpp' => -1000, // Invalid
            'selling_price' => 'not-a-number', // Invalid
        ]);

        $response->assertSessionHasErrors(['hpp', 'selling_price']);
    }

    public function test_admin_can_edit_product()
    {
        $product = Product::create([
            'sku' => 'SKU-EDIT',
            'product_name' => 'Lama',
            'hpp' => 50000,
            'selling_price' => 100000,
        ]);

        $response = $this->actingAs($this->admin)->put(route('product.update', $product->id), [
            'sku' => 'SKU-EDIT',
            'product_name' => 'Baru',
            'hpp' => 55000,
            'selling_price' => 110000,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => 'Baru',
            'hpp' => 55000,
        ]);
    }

    public function test_admin_can_toggle_product_status()
    {
        $product = Product::create([
            'sku' => 'SKU-STATUS',
            'product_name' => 'Status Test',
            'hpp' => 50000,
            'selling_price' => 100000,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->patch(route('product.toggle-status', $product->id));

        $response->assertSessionHas('success');
        $this->assertEquals('inactive', $product->fresh()->status);
        
        $response = $this->actingAs($this->admin)->patch(route('product.toggle-status', $product->id));
        $this->assertEquals('active', $product->fresh()->status);
    }
}
