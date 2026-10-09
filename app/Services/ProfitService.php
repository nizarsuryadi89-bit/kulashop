<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ImportError;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitService
{
    /**
     * Menghitung Laba Kotor (Gross Profit) dari sebuah pesanan.
     */
    public function getOrderProfit(Order $order): ?float
    {
        if ($order->hpp_status === 'Incomplete' || $order->income_status === 'Incomplete') {
            return null;
        }

        return $order->financial->total_income - $order->total_hpp;
    }

    /**
     * Menghitung Margin Kotor (Gross Margin) dari sebuah pesanan.
     */
    public function getOrderMargin(Order $order): ?float
    {
        $profit = $this->getOrderProfit($order);
        if ($profit === null) {
            return null;
        }

        $income = $order->financial->total_income;
        if (! $income || $income == 0) {
            return null;
        }

        return ($profit / $income) * 100;
    }

    /**
     * Menentukan status kelengkapan data profit pesanan.
     */
    public function getOrderProfitStatus(Order $order): string
    {
        if (! $order->financial || $order->financial->total_income === null) {
            return 'Not Available';
        }
        if ($order->hpp_status === 'Incomplete') {
            return 'Profit Incomplete';
        }

        return 'Profit Available';
    }

    /**
     * Get aggregate summary of profit and margin for a date range and filters
     */
    public function getProfitSummary(array $filters = []): array
    {
        $baseQuery = Order::query();
        $this->applyFilters($baseQuery, $filters);

        $totalOrdersCount = (clone $baseQuery)->count();
        $waitingIncomeCount = (clone $baseQuery)->whereDoesntHave('financial')->count();

        // 1. Incomplete Orders Count
        $incompleteQuery = (clone $baseQuery)->has('financial')->where(function ($q) {
            $q->whereDoesntHave('items', function ($sq) {
                $sq->whereNotNull('hpp_snapshot');
            })->orWhereHas('items', function ($sq) {
                $sq->whereNull('hpp_snapshot');
            });
        });
        $incompleteOrdersCount = $incompleteQuery->count();

        // Subquery for HPP calculation per order
        $hppSubQuery = DB::table('order_items')
            ->select('order_id', DB::raw('SUM((CASE WHEN (qty - COALESCE(returned_qty, 0)) > 0 THEN (qty - COALESCE(returned_qty, 0)) ELSE 0 END) * hpp_snapshot) as total_hpp'))
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) = COUNT(hpp_snapshot)');

        // 2. Aggregate Totals
        $totals = (clone $baseQuery)
            ->join('order_financials as f', 'orders.id', '=', 'f.order_id')
            ->joinSub($hppSubQuery, 'oi_agg', 'orders.id', '=', 'oi_agg.order_id')
            ->select(
                DB::raw('COUNT(orders.id) as complete_orders_count'),
                DB::raw('SUM(orders.total_payment) as total_payment'),
                DB::raw('SUM(f.total_income) as total_income'),
                DB::raw('SUM(f.admin_fee) as admin_fee'),
                DB::raw('SUM(f.order_processing_fee) as order_processing_fee'),
                DB::raw('SUM(f.transaction_fee) as transaction_fee'),
                DB::raw('SUM(f.service_fee) as service_fee'),
                DB::raw('SUM(f.promotion_fee) as promotion_fee'),
                DB::raw('SUM(f.affiliate_fee) as affiliate_fee'),
                DB::raw('SUM(f.other_fee) as other_fee'),
                DB::raw('SUM(f.refund_amount) as refund_amount'),
                DB::raw('SUM(f.tax_amount) as tax_amount'),
                DB::raw('SUM(oi_agg.total_hpp) as total_hpp')
            )
            ->toBase()
            ->first();

        $totalPayment = $totals->total_payment ?? 0;
        $totalIncome = $totals->total_income ?? 0;
        $totalHpp = $totals->total_hpp ?? 0;
        $completeOrdersCount = $totals->complete_orders_count ?? 0;

        $grossProfit = $totalIncome - $totalHpp;
        $grossMargin = $totalIncome > 0 ? ($grossProfit / $totalIncome) * 100 : null;

        $fees = [
            'admin_fee' => $totals->admin_fee ?? 0,
            'order_processing_fee' => $totals->order_processing_fee ?? 0,
            'transaction_fee' => $totals->transaction_fee ?? 0,
            'service_fee' => $totals->service_fee ?? 0,
            'promotion_fee' => $totals->promotion_fee ?? 0,
            'affiliate_fee' => $totals->affiliate_fee ?? 0,
            'other_fee' => $totals->other_fee ?? 0,
            'refund_amount' => $totals->refund_amount ?? 0,
            'tax_amount' => $totals->tax_amount ?? 0,
        ];

        // 3. City Data Aggregation
        $cityProfits = (clone $baseQuery)
            ->join('order_financials as f', 'orders.id', '=', 'f.order_id')
            ->joinSub($hppSubQuery, 'oi_agg', 'orders.id', '=', 'oi_agg.order_id')
            ->join('customers as c', 'orders.customer_id', '=', 'c.id')
            ->whereNotNull('c.city')
            ->where('c.city', '!=', '')
            ->select(
                'c.city',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(f.total_income) as income'),
                DB::raw('SUM(oi_agg.total_hpp) as hpp')
            )
            ->groupBy('c.city')
            ->get()
            ->map(function ($city) {
                $grossProfit = $city->income - $city->hpp;

                return [
                    'city' => $city->city,
                    'order_count' => $city->order_count,
                    'income' => $city->income,
                    'hpp' => $city->hpp,
                    'gross_profit' => $grossProfit,
                    'margin' => $city->income > 0 ? ($grossProfit / $city->income) * 100 : null,
                ];
            })
            ->sortByDesc('gross_profit')
            ->values()
            ->toArray();

        // 4. Customer Data Aggregation
        $customerProfits = (clone $baseQuery)
            ->join('order_financials as f', 'orders.id', '=', 'f.order_id')
            ->joinSub($hppSubQuery, 'oi_agg', 'orders.id', '=', 'oi_agg.order_id')
            ->join('customers as c', 'orders.customer_id', '=', 'c.id')
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(c.customer_name), ''), NULLIF(TRIM(c.username), ''), 'GUEST / UNKNOWN') as customer"),
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(f.total_income) as income'),
                DB::raw('SUM(oi_agg.total_hpp) as hpp')
            )
            ->groupBy('customer')
            ->get()
            ->map(function ($cust) {
                $grossProfit = $cust->income - $cust->hpp;

                return [
                    'customer' => $cust->customer,
                    'order_count' => $cust->order_count,
                    'income' => $cust->income,
                    'hpp' => $cust->hpp,
                    'gross_profit' => $grossProfit,
                    'margin' => $cust->income > 0 ? ($grossProfit / $cust->income) * 100 : null,
                ];
            })
            ->sortByDesc('gross_profit')
            ->values()
            ->toArray();

        // 5. Trend Calculation (Calling existing getProfitTrend for consistency)
        $trend = $this->getProfitTrend($filters);

        // Calculate Unmatched Income (Income Tanpa Order)
        $unmatchedIncome = 0;
        $unmatchedIncomeCount = 0;
        $errorQuery = ImportError::where('error_type', 'order_not_found');
        if (! empty($filters['start_date'])) {
            $errorQuery->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (! empty($filters['end_date'])) {
            $errorQuery->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // We could filter by marketplace or SKU if applicable, but usually unmatched means no order.
        $unmatchedErrors = $errorQuery->get();
        foreach ($unmatchedErrors as $error) {
            $row = $error->raw_data;
            if (is_array($row)) {
                $income = $row['total_penghasilan'] ?? $row['penghasilan'] ?? 0;
                // Basic cleanup for Indonesian number format
                if (is_string($income)) {
                    $income = str_replace(['Rp', '.', ' '], '', $income);
                    $income = str_replace(',', '.', $income);
                }
                $unmatchedIncome += (float) $income;
                $unmatchedIncomeCount++;
            }
        }

        // Calculate Operating Expenses for the same period
        $expenseQuery = Expense::query()->where('status', 'paid');
        if (! empty($filters['start_date'])) {
            $expenseQuery->where('expense_date', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }
        if (! empty($filters['end_date'])) {
            $expenseQuery->where('expense_date', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }
        $totalOperatingExpenses = (float) $expenseQuery->sum('amount');

        $netProfit = $grossProfit - $totalOperatingExpenses;
        $netMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : null;

        return [
            'total_payment' => $totalPayment,
            'total_income' => $totalIncome,
            'total_hpp' => $totalHpp,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMargin,
            'total_operating_expenses' => $totalOperatingExpenses,
            'net_profit' => $netProfit,
            'net_margin' => $netMargin,
            'fees_breakdown' => $fees,
            'orders_count' => $totalOrdersCount,
            'complete_orders_count' => $completeOrdersCount,
            'incomplete_orders_count' => $incompleteOrdersCount,
            'waiting_income_count' => $waitingIncomeCount,
            'unmatched_income' => $unmatchedIncome,
            'unmatched_income_count' => $unmatchedIncomeCount,
            'trend' => $trend,
            'city_profits' => $cityProfits,
            'customer_profits' => $customerProfits,
        ];
    }

    /**
     * Get profit by product using proportional allocation
     */
    public function getProductProfit(array $filters = []): array
    {
        // To get profit by product, we need to join order_items, orders, and order_financials
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
            ->whereNotNull('order_items.hpp_snapshot')
            ->whereNotExists(function ($query) {
                // Ensure no items in the order have null HPP
                $query->select(DB::raw(1))
                    ->from('order_items as oi2')
                    ->whereColumn('oi2.order_id', 'orders.id')
                    ->whereNull('oi2.hpp_snapshot');
            })
            ->select(
                'order_items.marketplace_sku',
                'order_items.product_name',
                DB::raw('SUM(CASE WHEN (order_items.qty - COALESCE(order_items.returned_qty, 0)) > 0 THEN (order_items.qty - COALESCE(order_items.returned_qty, 0)) ELSE 0 END) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as item_sales'),
                DB::raw('SUM(CASE WHEN (order_items.qty - COALESCE(order_items.returned_qty, 0)) > 0 THEN (order_items.qty - COALESCE(order_items.returned_qty, 0)) ELSE 0 END * order_items.hpp_snapshot) as item_hpp'),
                // Proportional Allocation of Order Income: (Item Subtotal / Order Subtotal) * Total Income
                DB::raw('SUM(((order_items.subtotal * 1.0) / NULLIF((SELECT SUM(subtotal) FROM order_items oi3 WHERE oi3.order_id = orders.id), 0)) * order_financials.total_income) as allocated_income')
            )
            ->groupBy('order_items.marketplace_sku', 'order_items.product_name');

        // Apply filters (we use prefix 'orders.' here for order_date and order_status)
        if (! empty($filters['start_date'])) {
            $query->whereDate('orders.order_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('orders.order_date', '<=', $filters['end_date']);
        }
        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('orders.order_status', $filters['order_status']);
        }
        if (! empty($filters['sku'])) {
            $query->where('order_items.marketplace_sku', 'like', '%'.$filters['sku'].'%');
        }

        $products = $query->get()->map(function ($product) {
            $allocatedIncome = $product->allocated_income ?? 0;
            $itemHpp = $product->item_hpp ?? 0;

            $grossProfit = $allocatedIncome - $itemHpp;
            $grossMargin = $allocatedIncome > 0 ? ($grossProfit / $allocatedIncome) * 100 : null;

            return [
                'sku' => $product->marketplace_sku,
                'name' => $product->product_name,
                'qty' => $product->total_qty,
                'sales' => $product->item_sales,
                'income' => $allocatedIncome,
                'hpp' => $itemHpp,
                'gross_profit' => $grossProfit,
                'margin' => $grossMargin,
            ];
        });

        $byProfit = $products->sortByDesc('gross_profit')->values()->toArray();
        $byMargin = $products->filter(function ($p) {
            return $p['margin'] !== null;
        })->sortByDesc('margin')->values()->toArray();

        return [
            'by_profit' => $byProfit,
            'by_margin' => $byMargin,
        ];
    }

    /**
     * Get orders that are missing HPP
     */
    public function getOrdersMissingHpp(array $filters = [])
    {
        $query = Order::query()
            ->whereHas('items', function ($q) {
                $q->whereNull('hpp_snapshot');
            });

        $this->applyFilters($query, $filters);

        return $query->with('items')->get();
    }

    /**
     * Get Profit Trend data (Income, HPP, Gross Profit, Expenses, Net Profit) aggregated by Date
     */
    public function getProfitTrend(array $filters = []): array
    {
        $baseQuery = Order::query();
        $this->applyFilters($baseQuery, $filters);

        $hppSubQuery = DB::table('order_items')
            ->select('order_id', DB::raw('SUM((CASE WHEN (qty - COALESCE(returned_qty, 0)) > 0 THEN (qty - COALESCE(returned_qty, 0)) ELSE 0 END) * hpp_snapshot) as total_hpp'))
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) = COUNT(hpp_snapshot)');

        $trendRaw = (clone $baseQuery)
            ->join('order_financials as f', 'orders.id', '=', 'f.order_id')
            ->joinSub($hppSubQuery, 'oi_agg', 'orders.id', '=', 'oi_agg.order_id')
            ->select(
                DB::raw('DATE(orders.order_date) as raw_date'),
                DB::raw('SUM(f.total_income) as income'),
                DB::raw('SUM(oi_agg.total_hpp) as hpp')
            )
            ->groupBy(DB::raw('DATE(orders.order_date)'))
            ->orderBy('raw_date')
            ->get();

        // Get expenses grouped by date
        $expenseQuery = Expense::query()
            ->where('status', 'paid')
            ->select(DB::raw('DATE(expense_date) as raw_date'), DB::raw('SUM(amount) as total_expenses'))
            ->groupBy(DB::raw('DATE(expense_date)'));

        if (! empty($filters['start_date'])) {
            $expenseQuery->whereDate('expense_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $expenseQuery->whereDate('expense_date', '<=', $filters['end_date']);
        }
        $expensesByDate = $expenseQuery->pluck('total_expenses', 'raw_date')->toArray();

        // Collect all dates from both sales and expenses
        $allDates = $trendRaw->pluck('raw_date')->toArray();
        $allDates = array_unique(array_merge($allDates, array_keys($expensesByDate)));
        sort($allDates);

        // Fill in missing dates to create a continuous trend line
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $currentDate = Carbon::parse($filters['start_date']);
            $endDate = Carbon::parse($filters['end_date']);
            $allDates = [];
            while ($currentDate->lte($endDate)) {
                $allDates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }
        } elseif (! empty($allDates)) {
            $currentDate = Carbon::parse(min($allDates));
            $endDate = Carbon::parse(max($allDates));
            $allDates = [];
            while ($currentDate->lte($endDate)) {
                $allDates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }
        }

        $salesByDate = $trendRaw->keyBy('raw_date')->toArray();

        $trendData = [];
        foreach ($allDates as $rawDate) {
            $income = $salesByDate[$rawDate]['income'] ?? 0;
            $hpp = $salesByDate[$rawDate]['hpp'] ?? 0;
            $grossProfit = $income - $hpp;
            $expense = $expensesByDate[$rawDate] ?? 0;
            $netProfit = $grossProfit - $expense;

            $trendData[] = [
                'date' => Carbon::parse($rawDate)->format('d M Y'),
                'raw_date' => $rawDate,
                'income' => $income,
                'hpp' => $hpp,
                'gross_profit' => $grossProfit,
                'expense' => $expense,
                'net_profit' => $netProfit,
            ];
        }

        return $trendData;
    }

    /**
     * Helper to apply common order filters
     */
    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['start_date'])) {
            $query->whereDate('orders.order_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('orders.order_date', '<=', $filters['end_date']);
        }
        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('orders.order_status', $filters['order_status']);
        }
        if (! empty($filters['marketplace_account_id']) && $filters['marketplace_account_id'] !== 'all') {
            $query->where('orders.marketplace_account_id', $filters['marketplace_account_id']);
        }
        if (! empty($filters['sku'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('marketplace_sku', 'like', '%'.$filters['sku'].'%');
            });
        }
    }
}
