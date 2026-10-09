<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'marketplaceAccount', 'items.product', 'financial'])->orderBy('order_date', 'desc');

        // Optional filtering by marketplace
        if ($request->has('marketplace_account_id') && $request->marketplace_account_id != '') {
            $query->where('marketplace_account_id', $request->marketplace_account_id);
        }

        // Default to current month if not explicitly provided
        $month = $request->has('month') ? $request->month : date('Y-m');

        if ($month != '') {
            $query->where('order_date', 'like', "{$month}%");
        }

        // Pass the resolved month to the view
        $currentMonth = $month;

        // Search by external_order_id, customer name or phone
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('external_order_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Calculate counts for tabs
        $baseQuery = clone $query;
        $countAll = $baseQuery->count();
        $countSuccess = (clone $baseQuery)->where('order_status', 'COMPLETED')->count();
        $countCancel = (clone $baseQuery)->whereIn('order_status', ['CANCELLED', 'RETURNED'])->count();

        // Filter by Status (Tab)
        $currentStatus = $request->get('status', 'all');
        if ($currentStatus === 'success') {
            $query->where('order_status', 'COMPLETED');
        } elseif ($currentStatus === 'cancel') {
            $query->whereIn('order_status', ['CANCELLED', 'RETURNED']);
        }

        // Calculate total revenue for the current filter (only COMPLETED orders)
        $totalRevenueQuery = clone $query;
        if ($currentStatus !== 'success') {
            $totalRevenueQuery->where('order_status', 'COMPLETED');
        }
        $totalRevenue = $totalRevenueQuery->sum('orders.total_payment');

        // Calculate total income (from income sheet data)
        $totalIncome = clone $totalRevenueQuery;
        $totalIncome = $totalIncome->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
            ->sum('order_financials.total_income');

        $orders = $query->paginate(20)->withQueryString();
        $marketplaces = MarketplaceAccount::all();

        return view('order.index', compact('orders', 'marketplaces', 'totalRevenue', 'totalIncome', 'currentMonth', 'currentStatus', 'countAll', 'countSuccess', 'countCancel'));
    }

    public function create()
    {
        $customers = Customer::orderBy('customer_name')->get();
        $marketplaces = MarketplaceAccount::where('status', 'active')->orderBy('account_name')->get();
        $products = Product::where('status', 'active')->orderBy('product_name')->get();

        return view('order.create', compact('customers', 'marketplaces', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'marketplace_account_id' => 'required|exists:marketplace_accounts,id',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'order_status' => 'required|string',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $orderId = 'MANUAL-'.strtoupper(uniqid());

        // Calculate grand total
        $grandTotal = 0;
        foreach ($validated['products'] as $item) {
            $grandTotal += $item['quantity'] * $item['price'];
        }

        $order = Order::create([
            'marketplace_account_id' => $validated['marketplace_account_id'],
            'external_order_id' => $orderId,
            'customer_id' => $validated['customer_id'],
            'order_status' => $validated['order_status'],
            'order_date' => $validated['order_date'],
            'total_payment' => $grandTotal,
        ]);

        foreach ($validated['products'] as $item) {
            $product = Product::find($item['id']);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'qty' => $item['quantity'],
                'selling_price' => $item['price'],
                'original_price' => $item['price'],
                'discount' => 0,
                'subtotal' => $item['quantity'] * $item['price'],
                'hpp_snapshot' => $product->hpp,
            ]);
        }

        return redirect()->route('order.index')->with('success', 'Pesanan manual berhasil dibuat.');
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'marketplaceAccount', 'items.product', 'financial']);
        return view('order.show', compact('order'));
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('order.index')->with('success', 'Pesanan berhasil dihapus.');
    }
}
