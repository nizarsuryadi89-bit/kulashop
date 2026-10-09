<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceAccount;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductHppHistory;
use App\Models\ProductMarketplace;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');
        $mappingStatus = $request->input('mapping_status');

        $products = $this->productService->getFilteredProducts($search, $status, $category, $mappingStatus);

        // Ambil daftar kategori unik untuk dropdown filter
        $categories = Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');

        $aliases = ProductMarketplace::with('marketplaceAccount')
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->groupBy('product_id');

        return view('product.index', compact('products', 'aliases', 'categories'));
    }

    public function create()
    {
        return view('product.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|unique:products,sku',
            'product_name' => 'required|string|max:255',
            'variant_name' => 'nullable|string|max:255',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $product = Product::create($validated);

            if ($product->hpp > 0) {
                ProductHppHistory::create([
                    'product_id' => $product->id,
                    'old_hpp' => 0,
                    'new_hpp' => $product->hpp,
                    'reason' => 'Initial HPP',
                    'changed_by' => auth()->id(),
                    'effective_at' => now(),
                ]);
            }
        });

        return redirect()->route('product.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('product.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => 'required|unique:products,sku,'.$product->id,
            'product_name' => 'required|string|max:255',
            'variant_name' => 'nullable|string|max:255',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($product, $validated) {
            $oldHpp = $product->hpp;
            $product->update($validated);

            if ($oldHpp != $product->hpp) {
                ProductHppHistory::create([
                    'product_id' => $product->id,
                    'old_hpp' => $oldHpp,
                    'new_hpp' => $product->hpp,
                    'reason' => 'Manual Update',
                    'changed_by' => auth()->id(),
                    'effective_at' => now(),
                ]);
            }
        });

        return redirect()->route('product.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function updateHpp(Request $request, Product $product, \App\Services\HppService $hppService)
    {
        $validated = $request->validate([
            'new_hpp' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        if ($product->hpp == $validated['new_hpp']) {
            return redirect()->back()->with('info', 'HPP tidak berubah.');
        }

        $hppService->updateProductHpp(
            $product, 
            $validated['new_hpp'], 
            $validated['reason'], 
            auth()->id()
        );

        return redirect()->back()->with('success', 'HPP berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('product.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function show(Product $product)
    {
        $product->load(['productMarketplaces.marketplaceAccount.marketplace', 'hppHistories.changer']);

        $totalOrders = $product->orderItems()->distinct('order_id')->count('order_id');
        $totalQtySold = $product->orderItems()->sum('qty');

        return view('product.show', compact('product', 'totalOrders', 'totalQtySold'));
    }

    public function toggleStatus(Product $product)
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status produk berhasil diubah.');
    }

    public function unmapped()
    {
        $unmappedSkus = $this->productService->getUnmappedSkus();

        return view('product.unmapped', compact('unmappedSkus'));
    }

    public function syncUnmapped()
    {
        try {
            $count = $this->productService->syncUnmappedSkus();
            if ($count > 0) {
                return back()->with('success', "Berhasil mensinkronkan {$count} produk dari data transaksi.");
            }
            return back()->with('success', "Tidak ada produk baru yang perlu disinkronkan.");
        } catch (\Exception $e) {
            \Log::error('Sync Unmapped Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', "Terjadi kesalahan saat mensinkronkan produk. Silakan coba lagi.");
        }
    }
}
