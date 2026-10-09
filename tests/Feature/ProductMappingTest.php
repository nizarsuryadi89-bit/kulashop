<?php

namespace Tests\Feature;

use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Product;
use App\Models\ProductMarketplace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup initial user and marketplace
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);

        $this->marketplace = Marketplace::create([
            'name' => 'Shopee',
            'code' => 'shopee',
            'base_url' => 'https://shopee.co.id',
        ]);

        $this->account = MarketplaceAccount::create([
            'marketplace_id' => $this->marketplace->id,
            'account_name' => 'Shopee Store 1',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'sku' => 'INT-001',
            'product_name' => 'Baju Merah Internal',
            'hpp' => 50000,
            'selling_price' => 100000,
            'stock' => 10,
        ]);
    }

    public function test_can_view_mapping_page()
    {
        $response = $this->actingAs($this->admin)->get(route('product.mapping', $this->product->id));

        $response->assertStatus(200);
        $response->assertSee('Mapping SKU Marketplace');
        $response->assertSee($this->product->product_name);
        $response->assertSee($this->account->account_name);
    }

    public function test_can_store_new_mapping()
    {
        $response = $this->actingAs($this->admin)->post(route('product.mapping.store', $this->product->id), [
            'marketplace_account_id' => $this->account->id,
            'marketplace_sku' => 'SHP-001-RED',
            'marketplace_product_name' => 'Baju Merah Shopee',
            'marketplace_variant' => 'Merah',
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseHas('product_marketplace', [
            'product_id' => $this->product->id,
            'marketplace_account_id' => $this->account->id,
            'marketplace_sku' => 'SHP-001-RED',
            'is_active' => 1,
        ]);
    }

    public function test_cannot_store_duplicate_mapping_for_same_account()
    {
        // Insert first mapping
        ProductMarketplace::create([
            'product_id' => $this->product->id,
            'marketplace_account_id' => $this->account->id,
            'marketplace_sku' => 'SHP-001-RED',
            'is_active' => true,
        ]);

        // Try to insert the same SKU for the same account
        $response = $this->actingAs($this->admin)->post(route('product.mapping.store', $this->product->id), [
            'marketplace_account_id' => $this->account->id,
            'marketplace_sku' => 'SHP-001-RED',
        ]);

        $response->assertSessionHasErrors('marketplace_sku');

        // Assert only 1 exists
        $this->assertEquals(1, ProductMarketplace::count());
    }

    public function test_can_delete_mapping()
    {
        $mapping = ProductMarketplace::create([
            'product_id' => $this->product->id,
            'marketplace_account_id' => $this->account->id,
            'marketplace_sku' => 'SHP-001-RED',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('product.mapping.destroy', [$this->product->id, $mapping->id]));

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseMissing('product_marketplace', [
            'id' => $mapping->id,
        ]);
    }

    public function test_viewer_cannot_manage_mapping()
    {
        $response = $this->actingAs($this->viewer)->get(route('product.mapping', $this->product->id));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_unmapped_skus_can_be_retrieved()
    {
        $customer = \App\Models\Customer::create([
            'marketplace_account_id' => $this->account->id,
            'username' => 'john',
            'customer_name' => 'John',
            'phone' => '0812'
        ]);

        // Insert an unmapped order item
        $order = \App\Models\Order::create([
            'marketplace_account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'external_order_id' => 'ORD-UNMAPPED-1',
            'order_status' => 'Selesai',
            'order_substatus' => 'Selesai',
            'total_payment' => 50000,
        ]);
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'marketplace_sku' => 'UNMAPPED-1',
            'product_id' => null,
            'product_name' => 'Produk A',
            'qty' => 5,
            'original_price' => 10000,
            'discounted_price' => 10000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('product.unmapped'));
        $response->assertStatus(200);
        $response->assertSee('UNMAPPED-1');
        $response->assertSee('Produk A');
    }

    public function test_can_auto_sync_unmapped_skus_from_transactions()
    {
        $customer = \App\Models\Customer::create([
            'marketplace_account_id' => $this->account->id,
            'username' => 'jane',
            'customer_name' => 'Jane',
            'phone' => '0813'
        ]);

        $order = \App\Models\Order::create([
            'marketplace_account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'external_order_id' => 'ORD-SYNC-1',
            'order_status' => 'Selesai',
            'order_substatus' => 'Selesai',
            'total_payment' => 150000,
        ]);
        
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'marketplace_sku' => 'SYNC-SKU-1',
            'product_id' => null,
            'product_name' => 'Produk Sync',
            'qty' => 1,
            'original_price' => 150000,
            'discounted_price' => 150000,
            'selling_price' => 150000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('product.unmapped.sync'));
        $response->assertSessionHas('success');

        // Verify product was created
        $this->assertDatabaseHas('products', [
            'sku' => 'SYNC-SKU-1',
            'product_name' => 'Produk Sync',
            'selling_price' => 150000,
            'hpp' => 0
        ]);

        $product = \App\Models\Product::where('sku', 'SYNC-SKU-1')->first();

        // Verify mapping was created
        $this->assertDatabaseHas('product_marketplace', [
            'marketplace_account_id' => $this->account->id,
            'marketplace_sku' => 'SYNC-SKU-1',
            'product_id' => $product->id
        ]);

        // Verify order item was updated
        $this->assertDatabaseHas('order_items', [
            'marketplace_sku' => 'SYNC-SKU-1',
            'product_id' => $product->id
        ]);
    }
}
