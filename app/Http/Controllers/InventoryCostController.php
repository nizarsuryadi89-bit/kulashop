<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryCost;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\ProductPurchasePriceHistory;
use App\Services\InventoryCostService;
use Illuminate\Support\Facades\DB;

class InventoryCostController extends Controller
{
    /**
     * Inventory Valuation Report
     */
    public function valuation(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $search = $request->input('search');

        $query = InventoryCost::with(['product.category', 'warehouse']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search) {
            $query->whereHas('product', function($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $valuations = $query->orderBy('inventory_value', 'desc')->paginate(20);
        $warehouses = Warehouse::all();
        
        $totalValue = InventoryCost::when($warehouseId, function($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        })->sum('inventory_value');
        
        $totalQty = InventoryCost::when($warehouseId, function($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        })->sum('quantity');

        return view('inventory.valuation.index', compact('valuations', 'warehouses', 'warehouseId', 'search', 'totalValue', 'totalQty'));
    }

    /**
     * Product Cost Report
     */
    public function report(Request $request)
    {
        $search = $request->input('search');

        $costs = InventoryCost::with(['product', 'warehouse'])
            ->when($search, function($q) use ($search) {
                $q->whereHas('product', function($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->paginate(20);

        // Fetch last purchase price for each
        foreach ($costs as $cost) {
            $lastPurchase = ProductPurchasePriceHistory::where('product_id', $cost->product_id)
                ->orderBy('created_at', 'desc')
                ->first();
            $cost->last_purchase_price = $lastPurchase ? $lastPurchase->purchase_price : 0;
            $cost->last_purchase_date = $lastPurchase ? $lastPurchase->created_at : null;
            
            $cost->cost_increase = 0;
            if ($cost->last_purchase_price > 0 && $cost->average_cost > 0) {
                $cost->cost_increase = (($cost->last_purchase_price - $cost->average_cost) / $cost->average_cost) * 100;
            }
        }

        return view('inventory.costing.report', compact('costs', 'search'));
    }

    /**
     * Cost Reconciliation
     */
    public function reconciliation(Request $request)
    {
        $search = $request->input('search');

        // We want to compare warehouse_stocks vs inventory_costs
        $stocks = WarehouseStock::with(['product', 'warehouse'])
            ->when($search, function($q) use ($search) {
                $q->whereHas('product', function($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->paginate(20);

        foreach ($stocks as $stock) {
            $cost = InventoryCost::where('product_id', $stock->product_id)
                ->where('warehouse_id', $stock->warehouse_id)
                ->first();
                
            $stock->cost_qty = $cost ? $cost->quantity : 0;
            $stock->difference = $stock->stock - $stock->cost_qty;
            $stock->status = $stock->difference == 0 ? 'MATCH' : 'MISMATCH';
        }

        return view('inventory.costing.reconciliation', compact('stocks', 'search'));
    }

    /**
     * Show Opening Stock Form
     */
    public function createOpeningStock()
    {
        $products = Product::where('status', 'active')->orderBy('product_name')->get();
        $warehouses = Warehouse::where('status', 'active')->get();
        return view('inventory.costing.opening_stock', compact('products', 'warehouses'));
    }

    /**
     * Store Opening Stock
     */
    public function storeOpeningStock(Request $request, InventoryCostService $service)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $warehouse = Warehouse::findOrFail($request->warehouse_id);
            
            // Also update WarehouseStock if you want opening stock to physically exist
            DB::transaction(function() use ($product, $warehouse, $request, $service) {
                // Add physical stock
                $whStock = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                    ['stock' => 0]
                );
                $whStock->increment('stock', $request->quantity);
                $product->increment('stock', $request->quantity);

                // Add to Costing
                $service->handleOpeningStock($product, $warehouse, $request->quantity, $request->unit_cost);
                
                // Add stock movement
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => 'in',
                    'quantity' => $request->quantity,
                    'reference_type' => 'opening_stock',
                    'description' => 'Opening Stock dari modul Costing',
                    'created_by' => auth()->id(),
                ]);
            });

            return redirect()->route('inventory.valuation')->with('success', 'Opening stock berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan opening stock: ' . $e->getMessage());
        }
    }
}
