<?php

namespace App\Services\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ProfitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OwnerDashboardService
{
    protected $profitService;

    public function __construct(ProfitService $profitService)
    {
        $this->profitService = $profitService;
    }

    /**
     * Get all key metrics and aggregated data for the Owner Dashboard
     */
    public function getDashboardMetrics(array $filters): array
    {
        $period = $filters['period'] ?? 'this_month';
        $startDateInput = $filters['start_date'] ?? null;
        $endDateInput = $filters['end_date'] ?? null;
        $marketplaceAccountId = $filters['marketplace_account_id'] ?? 'all';
        $orderStatus = $filters['order_status'] ?? 'all';

        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday()->endOfDay();
                break;
            case '7_days':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case '30_days':
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'custom':
                if ($startDateInput && $endDateInput) {
                    $startDate = Carbon::parse($startDateInput)->startOfDay();
                    $endDate = Carbon::parse($endDateInput)->endOfDay();
                } else {
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfDay();
                    $period = 'this_month';
                }
                break;
            case 'all_time':
                $startDate = null;
                $endDate = null;
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfDay();
                $period = 'this_month';
                break;
        }

        // 1. Total Metrics
        $orderQuery = Order::query();
        if ($startDate) {
            $orderQuery->whereBetween('order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $orderQuery->where('marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $orderQuery->where('order_status', $orderStatus);
        }

        $totalOrders = $orderQuery->count();

        $totalOmzetQuery = DB::table('orders');
        if ($startDate) {
            $totalOmzetQuery->whereBetween('order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $totalOmzetQuery->where('marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $totalOmzetQuery->where('order_status', $orderStatus);
        }

        $totalPayment = (float) $totalOmzetQuery->sum('total_payment');

        $completedOrders = (clone $orderQuery)->where('order_status', 'COMPLETED')->count();
        $totalCustomers = DB::table('orders')
            ->where(function ($query) use ($startDate, $endDate, $marketplaceAccountId, $orderStatus) {
                if ($startDate) {
                    $query->whereBetween('order_date', [$startDate, $endDate]);
                }
                if ($marketplaceAccountId !== 'all') {
                    $query->where('marketplace_account_id', $marketplaceAccountId);
                }
                if ($orderStatus !== 'all') {
                    $query->where('order_status', $orderStatus);
                }
            })
            ->distinct('customer_id')
            ->count('customer_id');

        $orderItemQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id');
        if ($startDate) {
            $orderItemQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $orderItemQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $orderItemQuery->where('orders.order_status', $orderStatus);
        }

        $totalProductsSold = (int) (clone $orderItemQuery)->sum('order_items.qty');

        $incomeQuery = DB::table('order_financials')
            ->join('orders', 'order_financials.order_id', '=', 'orders.id');
        if ($startDate) {
            $incomeQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $incomeQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $incomeQuery->where('orders.order_status', $orderStatus);
        }
        $marketplaceIncome = (float) $incomeQuery->sum('order_financials.total_income');

        // 2. Best Selling Products
        $bestSellingQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.product_name as internal_product_name',
                'order_items.product_name as marketplace_product_name',
                'order_items.marketplace_sku',
                DB::raw('MAX(products.stock) as current_stock'),
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders'),
                DB::raw('SUM(order_items.subtotal) as total_omzet')
            )
            ->groupBy('products.product_name', 'order_items.product_name', 'order_items.marketplace_sku')
            ->orderByDesc('total_qty')
            ->limit(10);

        if ($startDate) {
            $bestSellingQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $bestSellingQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $bestSellingQuery->where('orders.order_status', $orderStatus);
        } else {
            $bestSellingQuery->where('orders.order_status', '!=', 'CANCELLED');
        }
        $bestSellingProducts = $bestSellingQuery->get();

        // 3. Top Customers
        $topCustomersQuery = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                'customers.customer_name',
                'customers.username',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_payment) as total_belanja')
            )
            ->groupBy('customers.id', 'customers.customer_name', 'customers.username')
            ->orderByDesc('total_orders')
            ->limit(10);

        if ($startDate) {
            $topCustomersQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $topCustomersQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $topCustomersQuery->where('orders.order_status', $orderStatus);
        } else {
            $topCustomersQuery->where('orders.order_status', '!=', 'CANCELLED');
        }
        $topCustomers = $topCustomersQuery->get();

        if ($topCustomers->isNotEmpty()) {
            $customerIds = $topCustomers->pluck('id')->toArray();
            $customerQtyQuery = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.customer_id', $customerIds)
                ->select('orders.customer_id', DB::raw('SUM(order_items.qty) as total_qty'))
                ->groupBy('orders.customer_id');

            if ($startDate) {
                $customerQtyQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
            }
            if ($marketplaceAccountId !== 'all') {
                $customerQtyQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
            }
            if ($orderStatus !== 'all') {
                $customerQtyQuery->where('orders.order_status', $orderStatus);
            } else {
                $customerQtyQuery->where('orders.order_status', '!=', 'CANCELLED');
            }
            $customerQtys = $customerQtyQuery->pluck('total_qty', 'customer_id');

            foreach ($topCustomers as $c) {
                $c->total_qty = $customerQtys[$c->id] ?? 0;
            }
        }

        // 4. Top Cities
        $topCitiesQuery = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'customers.city',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_payment) as total_omzet')
            )
            ->whereNotNull('customers.city')
            ->where('customers.city', '!=', '')
            ->groupBy('customers.city')
            ->orderByDesc('total_orders')
            ->limit(10);

        if ($startDate) {
            $topCitiesQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $topCitiesQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $topCitiesQuery->where('orders.order_status', $orderStatus);
        } else {
            $topCitiesQuery->where('orders.order_status', '!=', 'CANCELLED');
        }
        $topCities = $topCitiesQuery->get();

        if ($topCities->isNotEmpty()) {
            $cities = $topCities->pluck('city')->toArray();
            $cityQtyQuery = DB::table('orders')
                ->join('customers', 'orders.customer_id', '=', 'customers.id')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('customers.city', $cities)
                ->select('customers.city', DB::raw('SUM(order_items.qty) as total_qty'))
                ->groupBy('customers.city');

            if ($startDate) {
                $cityQtyQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
            }
            if ($marketplaceAccountId !== 'all') {
                $cityQtyQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
            }
            if ($orderStatus !== 'all') {
                $cityQtyQuery->where('orders.order_status', $orderStatus);
            } else {
                $cityQtyQuery->where('orders.order_status', '!=', 'CANCELLED');
            }
            $cityQtys = $cityQtyQuery->pluck('total_qty', 'city');

            foreach ($topCities as $c) {
                $c->total_qty = $cityQtys[$c->city] ?? 0;
            }
        }

        // 5. Top Couriers (Expedisi)
        $topCouriersQuery = DB::table('orders')
            ->select(
                'shipping_provider',
                DB::raw('COUNT(id) as total_orders'),
                DB::raw('SUM(total_payment) as total_omzet')
            )
            ->whereNotNull('shipping_provider')
            ->where('shipping_provider', '!=', '')
            ->groupBy('shipping_provider')
            ->orderByDesc('total_orders')
            ->limit(10);

        if ($startDate) {
            $topCouriersQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $topCouriersQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $topCouriersQuery->where('orders.order_status', $orderStatus);
        } else {
            $topCouriersQuery->where('orders.order_status', '!=', 'CANCELLED');
        }
        $topCouriers = $topCouriersQuery->get();

        // 6. Low Stock Products
        $lowStockProducts = Product::where('status', 'active')
            ->where(function ($query) {
                $query->whereRaw('stock <= minimum_stock')
                    ->orWhere('stock', '<=', 0);
            })
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();

        // 6. Stock Summary
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $totalStockUnits = \App\Models\WarehouseStock::sum('qty');
        $lowStockCount = Product::where('status', 'active')->where('stock', '>', 0)->whereRaw('stock <= minimum_stock')->count();
        $outOfStockCount = Product::where('status', 'active')->where('stock', '<=', 0)->count();

        // 7. Sales By Account / Marketplace Breakdown
        $salesByAccountQuery = DB::table('orders')
            ->join('marketplace_accounts', 'orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->join('marketplaces', 'marketplace_accounts.marketplace_id', '=', 'marketplaces.id')
            ->leftJoin('order_financials', 'orders.id', '=', 'order_financials.order_id')
            ->select(
                'marketplaces.name as marketplace_name',
                'marketplace_accounts.account_name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_payment) as total_omzet'),
                DB::raw('SUM(order_financials.total_income) as total_income')
            )
            ->groupBy('marketplace_accounts.id', 'marketplace_accounts.account_name', 'marketplaces.name')
            ->orderByDesc('total_orders');

        if ($startDate) {
            $salesByAccountQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $salesByAccountQuery->where('orders.marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $salesByAccountQuery->where('orders.order_status', $orderStatus);
        }
        $salesByAccount = $salesByAccountQuery->get();

        // 8. Sales Trend
        $salesTrendQuery = DB::table('orders')
            ->select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('COUNT(id) as total_orders'),
                DB::raw('SUM(total_payment) as total_omzet')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc');

        if ($startDate) {
            $salesTrendQuery->whereBetween('order_date', [$startDate, $endDate]);
        } else {
            // For all time, limit to last 30 days to avoid massive charts
            $salesTrendQuery->where('order_date', '>=', now()->subDays(30)->startOfDay());
        }
        if ($marketplaceAccountId !== 'all') {
            $salesTrendQuery->where('marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $salesTrendQuery->where('order_status', $orderStatus);
        }
        $salesTrend = $salesTrendQuery->get();

        // Format trend for chart.js
        $trendDates = $salesTrend->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('d M');
        })->toArray();
        $trendOmzet = $salesTrend->pluck('total_omzet')->toArray();
        $trendOrders = $salesTrend->pluck('total_orders')->toArray();

        // 9. Recent Orders
        $recentOrdersQuery = Order::with(['marketplaceAccount.marketplace', 'customer'])
            ->orderByDesc('order_date')
            ->limit(10);

        if ($startDate) {
            $recentOrdersQuery->whereBetween('order_date', [$startDate, $endDate]);
        }
        if ($marketplaceAccountId !== 'all') {
            $recentOrdersQuery->where('marketplace_account_id', $marketplaceAccountId);
        }
        if ($orderStatus !== 'all') {
            $recentOrdersQuery->where('order_status', $orderStatus);
        }
        $recentOrders = $recentOrdersQuery->get();

        // 10. Profit Overview
        $profitSummary = null;
        $trendNetProfit = [];
        $trendGrossProfit = [];
        $trendProfitDates = [];
        
        if (auth()->check() && auth()->user()->isOwner()) {
            $profitSummary = $this->profitService->getProfitSummary([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'order_status' => $orderStatus,
                'marketplace_account_id' => $marketplaceAccountId,
            ]);
            
            if (isset($profitSummary['trend'])) {
                foreach ($profitSummary['trend'] as $trendItem) {
                    $trendProfitDates[] = Carbon::parse($trendItem['raw_date'])->format('d M');
                    $trendNetProfit[] = $trendItem['net_profit'];
                    $trendGrossProfit[] = $trendItem['gross_profit'];
                }
            }
        }

        return [
            'period' => $period,
            'totalOrders' => $totalOrders,
            'totalPayment' => $totalPayment,
            'completedOrders' => $completedOrders,
            'totalCustomers' => $totalCustomers,
            'totalProductsSold' => $totalProductsSold,
            'marketplaceIncome' => $marketplaceIncome,
            'bestSellingProducts' => $bestSellingProducts,
            'topCustomers' => $topCustomers,
            'topCities' => $topCities,
            'topCouriers' => $topCouriers,
            'lowStockProducts' => $lowStockProducts,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'totalStockUnits' => $totalStockUnits,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'salesByAccount' => $salesByAccount,
            'salesTrend' => $salesTrend,
            'trendDates' => $trendDates,
            'trendOmzet' => $trendOmzet,
            'trendOrders' => $trendOrders,
            'trendProfitDates' => $trendProfitDates,
            'trendNetProfit' => $trendNetProfit,
            'trendGrossProfit' => $trendGrossProfit,
            'recentOrders' => $recentOrders,
            'profitSummary' => $profitSummary,
        ];
    }
}
