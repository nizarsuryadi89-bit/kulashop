<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceAccount;
use App\Models\Product;
use App\Services\ProductMarketplaceService;
use Illuminate\Http\Request;

class ProductMarketplaceController extends Controller
{
    protected $mappingService;

    public function __construct(ProductMarketplaceService $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function index(Product $product)
    {
        $product->load(['productMarketplaces.marketplaceAccount.marketplace']);
        
        // Hanya tampilkan akun Shopee sesuai aturan Step 10
        $accounts = MarketplaceAccount::whereHas('marketplace', function ($q) {
                $q->where('name', 'like', '%Shopee%');
            })
            ->with('marketplace')
            ->where('status', 'active')
            ->get();

        return view('product.mapping', compact('product', 'accounts'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'marketplace_account_id' => 'required|exists:marketplace_accounts,id',
            'marketplace_sku' => 'required|string|max:255',
            'marketplace_product_id' => 'nullable|string|max:255',
            'marketplace_product_name' => 'nullable|string|max:255',
            'marketplace_variant' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->mappingService->storeMapping($product, $validated);
            return back()->with('success', 'Mapping SKU berhasil ditambahkan.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'sudah terhubung') || str_contains($e->getMessage(), 'sudah ada di akun ini')) {
                return back()->withInput()->withErrors(['marketplace_sku' => $e->getMessage()]);
            }
            
            \Log::error('Mapping Error: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'data' => $validated,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Terjadi kesalahan saat menyimpan data mapping. Silakan coba lagi.');
        }
    }

    public function destroy(Product $product, $mapping)
    {
        $this->mappingService->destroyMapping($mapping);
        return back()->with('success', 'Mapping SKU berhasil dihapus.');
    }
}
