<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Stock Summary Dashboard (/stock).
     */
    public function stockSummary(Request $request)
    {
        // KPI metrics
        $totalProducts = Product::where('status', 'active')->count();
        $totalSku = Product::where('status', 'active')->distinct('sku')->count('sku');
        $totalQty = (float) Product::where('status', 'active')->sum('stock');
        $lowStockCount = Product::where('status', 'active')->whereColumn('stock', '<=', 'minimum_stock')->where('stock', '>', 0)->count();
        $outOfStockCount = Product::where('status', 'active')->where('stock', '<=', 0)->count();
        $totalWarehouses = Warehouse::where('status', 'active')->count();
        $movementsToday = StockMovement::whereDate('created_at', today())->count();

        // Per-warehouse stock distribution
        $warehouseStocks = Warehouse::where('status', 'active')
            ->with(['stocks.product'])
            ->get()
            ->map(function ($wh) {
                return [
                    'name' => $wh->name,
                    'code' => $wh->code,
                    'is_default' => $wh->is_default,
                    'total_qty' => (float) $wh->stocks->sum('qty'),
                    'total_sku' => $wh->stocks->count(),
                ];
            });

        // Products with low/out-of-stock for alert table
        $alertProducts = Product::where('status', 'active')
            ->where(function ($q) {
                $q->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'minimum_stock');
            })
            ->orderBy('stock', 'asc')
            ->limit(20)
            ->get();

        // Recent 10 movements
        $recentMovements = StockMovement::with(['product', 'warehouse', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('inventory.stock_summary', compact(
            'totalProducts',
            'totalSku',
            'totalQty',
            'lowStockCount',
            'outOfStockCount',
            'totalWarehouses',
            'movementsToday',
            'warehouseStocks',
            'alertProducts',
            'recentMovements'
        ));
    }

    /**
     * Overview of inventory / stock per product and warehouse.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $search = $request->input('search');
        $stockFilter = $request->input('stock_filter'); // all, low, out, in_stock, normal

        $warehouses = Warehouse::where('status', 'active')->get();

        // Query berbasis WarehouseStock – satu baris = satu produk × satu gudang
        $query = WarehouseStock::with(['product', 'warehouse'])
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            });

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        // Filter status berdasarkan aturan: qty <= 0 = out, qty <= minimum_stock = low, else normal
        if ($stockFilter === 'out') {
            $query->where('qty', '<=', 0);
        } elseif ($stockFilter === 'low') {
            $query->where('qty', '>', 0)
                ->whereHas('product', function ($q) {
                    $q->whereNotNull('minimum_stock')
                        ->whereColumn('warehouse_stocks.qty', '<=', 'products.minimum_stock');
                });
        } elseif ($stockFilter === 'normal') {
            $query->where('qty', '>', 0)
                ->where(function ($q) {
                    $q->whereHas('product', function ($pq) {
                        $pq->whereNull('minimum_stock')
                            ->orWhereColumn('warehouse_stocks.qty', '>', 'products.minimum_stock');
                    });
                });
        } elseif ($stockFilter === 'in_stock') {
            $query->where('qty', '>', 0);
        }

        $stocks = $query->orderBy('qty', 'asc')->paginate(25)->withQueryString();

        // Summary counts (tetap berdasarkan Product aggregate)
        $totalProductsCount = Product::where('status', 'active')->count();
        $totalStockUnits = (float) Product::where('status', 'active')->sum('stock');
        $lowStockCount = Product::where('status', 'active')
            ->whereNotNull('minimum_stock')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->where('stock', '>', 0)
            ->count();
        $outOfStockCount = Product::where('status', 'active')->where('stock', '<=', 0)->count();

        return view('inventory.index', compact(
            'stocks',
            'warehouses',
            'warehouseId',
            'search',
            'stockFilter',
            'totalProductsCount',
            'totalStockUnits',
            'lowStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Show Stock In form.
     */
    public function stockIn()
    {
        $products = Product::where('status', 'active')->orderBy('product_name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('is_default', 'desc')->get();

        return view('inventory.stock_in', compact('products', 'warehouses'));
    }

    /**
     * Store Stock In.
     */
    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'reference_type' => 'required|in:manual_in,opening',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $notes = $validated['notes'] ?? ($validated['reference_type'] === 'opening' ? 'Stok Awal' : 'Penerimaan Barang Masuk');
            
            $movement = $this->inventoryService->increaseStock(
                (int) $validated['product_id'],
                (int) $validated['warehouse_id'],
                (float) $validated['quantity'],
                $notes,
                $validated['reference_type'],
                null,
                auth()->id()
            );

            return redirect()->route('inventory.index')->with('success', "Stok masuk berhasil dicatat (#{$movement->movement_number}).");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock In Error: ' . $e->getMessage(), ['exception' => $e]);
            $message = $e instanceof \Illuminate\Database\QueryException 
                ? 'Terjadi kesalahan sistem saat memproses transaksi database.' 
                : $e->getMessage();
            return back()->withInput()->with('error', 'Gagal mencatat stok masuk: ' . $message);
        }
    }

    /**
     * Show Stock Out form.
     */
    public function stockOut()
    {
        $products = Product::where('status', 'active')->orderBy('product_name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('is_default', 'desc')->get();

        return view('inventory.stock_out', compact('products', 'warehouses'));
    }

    /**
     * Store Stock Out.
     */
    public function storeStockOut(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $movement = $this->inventoryService->decreaseStock(
                (int) $validated['product_id'],
                (int) $validated['warehouse_id'],
                (float) $validated['quantity'],
                $validated['notes'] ?? 'Pengeluaran Barang Manual',
                'manual_out',
                null,
                auth()->id(),
                false // Do not allow negative stock for manual out
            );

            return redirect()->route('inventory.index')->with('success', "Stok keluar berhasil dicatat (#{$movement->movement_number}).");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Out Error: ' . $e->getMessage(), ['exception' => $e]);
            $message = $e instanceof \Illuminate\Database\QueryException 
                ? 'Terjadi kesalahan sistem saat memproses transaksi database.' 
                : $e->getMessage();
            return back()->withInput()->with('error', $message);
        }
    }

    /**
     * Show Stock Adjustment / Opname form.
     */
    public function adjustment(Request $request)
    {
        $selectedProductId = $request->input('product_id');
        $selectedWarehouseId = $request->input('warehouse_id');

        $products = Product::where('status', 'active')->orderBy('product_name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('is_default', 'desc')->get();

        $currentStock = 0;
        if ($selectedProductId && $selectedWarehouseId) {
            $whStock = WarehouseStock::where('product_id', $selectedProductId)
                ->where('warehouse_id', $selectedWarehouseId)
                ->first();
            $currentStock = $whStock ? $whStock->qty : 0;
        }

        return view('inventory.adjustment', compact('products', 'warehouses', 'selectedProductId', 'selectedWarehouseId', 'currentStock'));
    }

    /**
     * Store Stock Adjustment / Opname.
     */
    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'actual_stock' => 'required|numeric|min:0',
            'notes' => 'required|string|max:255',
        ]);

        try {
            $movement = $this->inventoryService->adjustStock(
                (int) $validated['product_id'],
                (int) $validated['warehouse_id'],
                (float) $validated['actual_stock'],
                $validated['notes'],
                auth()->id()
            );

            return redirect()->route('inventory.index')->with('success', "Penyesuaian stok opname berhasil dicatat (#{$movement->movement_number}).");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Adjustment Error: ' . $e->getMessage(), ['exception' => $e]);
            $message = $e instanceof \Illuminate\Database\QueryException 
                ? 'Terjadi kesalahan sistem saat memproses transaksi database.' 
                : $e->getMessage();
            return back()->withInput()->with('error', 'Gagal menyimpan penyesuaian stok: ' . $message);
        }
    }

    /**
     * View Stock Card (Kartu Stok / Ledger) for a specific product.
     */
    public function stockCard(Product $product, Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $warehouses = Warehouse::where('status', 'active')->get();
        $movements = $this->inventoryService->getStockCard($product->id, $warehouseId, $startDate, $endDate);

        $warehouseStocks = WarehouseStock::with('warehouse')
            ->where('product_id', $product->id)
            ->get();

        return view('inventory.stock_card', compact('product', 'movements', 'warehouses', 'warehouseId', 'startDate', 'endDate', 'warehouseStocks'));
    }

    /**
     * View Global Stock Movements.
     */
    public function stockMovements(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $productId = $request->input('product_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $warehouses = Warehouse::where('status', 'active')->get();
        $products = Product::where('status', 'active')->orderBy('product_name')->get();

        $query = StockMovement::with(['product', 'warehouse', 'user'])->latest();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($productId) {
            $query->where('product_id', $productId);
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $movements = $query->paginate(50)->withQueryString();

        return view('inventory.stock_movements', compact('movements', 'warehouses', 'products', 'warehouseId', 'productId', 'startDate', 'endDate'));
    }

    /**
     * View Low Stock & Out of Stock products monitoring.
     */
    public function lowStock(Request $request)
    {
        $products = $this->inventoryService->getLowStockProducts(null, 25);
        $warehouses = Warehouse::where('status', 'active')->get();

        return view('inventory.low_stock', compact('products', 'warehouses'));
    }
}
