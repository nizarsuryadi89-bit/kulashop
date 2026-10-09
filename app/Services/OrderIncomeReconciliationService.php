<?php

namespace App\Services;

use App\Models\ImportError;
use App\Models\Order;

class OrderIncomeReconciliationService
{
    /**
     * Get detailed reconciliation status for a specific order.
     */
    public function reconcileOrder(Order $order): array
    {
        $financial = $order->financial;

        $orderPayment = (float) $order->total_payment;
        $incomePayment = $financial ? (float) $financial->total_payment : 0.0;
        $diff = $orderPayment - $incomePayment;
        $hasDifference = abs($diff) > 0.01;

        if (! $financial) {
            $status = 'waiting_income';
            $paymentStatus = 'NOT AVAILABLE';
        } else {
            $status = $hasDifference ? 'needs_review' : 'matched';
            $paymentStatus = $hasDifference ? 'DIFFERENT' : 'MATCH';
        }

        return [
            'status' => $status,
            'payment_status' => $paymentStatus,
            'order_payment' => $orderPayment,
            'income_payment' => $incomePayment,
            'payment_difference' => $diff,
        ];
    }

    /**
     * Get summary metrics for reconciliation
     */
    public function getSummary(string $marketplaceCode, array $filters = []): array
    {
        $marketplaceAccountId = $filters['toko'] ?? null;
        $dateType = $filters['date_type'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        // Base Query untuk Order
        $baseOrderQuery = Order::whereHas('marketplaceAccount.marketplace', function ($q) use ($marketplaceCode) {
            $q->where('code', $marketplaceCode);
        });

        if ($marketplaceAccountId) {
            $baseOrderQuery->where('marketplace_account_id', $marketplaceAccountId);
        }

        if ($dateType && $startDate && $endDate) {
            // Add time to cover full day
            $start = $startDate.' 00:00:00';
            $end = $endDate.' 23:59:59';

            if ($dateType == 'order_date') {
                $baseOrderQuery->whereBetween('order_date', [$start, $end]);
            } elseif ($dateType == 'released_at') {
                $baseOrderQuery->whereHas('financial', function ($q) use ($start, $end) {
                    $q->whereBetween('released_at', [$start, $end]);
                });
            }
        }

        $totalMatched = (clone $baseOrderQuery)
            ->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
            ->whereRaw('ABS(orders.total_payment - order_financials.total_payment) <= 0.01')
            ->count('orders.id');

        $totalWaitingIncome = (clone $baseOrderQuery)
            ->where('order_status', '!=', 'CANCELLED')
            ->whereDoesntHave('financial')
            ->count();

        $totalSelisih = (clone $baseOrderQuery)
            ->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
            ->whereRaw('ABS(orders.total_payment - order_financials.total_payment) > 0.01')
            ->count('orders.id');

        // For import_errors (Waiting Order)
        $errorQuery = ImportError::where('error_type', 'order_not_found')
            ->whereHas('importBatch.marketplaceAccount.marketplace', function ($q) use ($marketplaceCode) {
                $q->where('code', $marketplaceCode);
            });

        if ($marketplaceAccountId) {
            $errorQuery->whereHas('importBatch', function ($q) use ($marketplaceAccountId) {
                $q->where('marketplace_account_id', $marketplaceAccountId);
            });
        }

        if ($dateType && $startDate && $endDate) {
            $start = $startDate.' 00:00:00';
            $end = $endDate.' 23:59:59';
            // We use created_at of the error as an approximation since it corresponds to import time
            $errorQuery->whereBetween('created_at', [$start, $end]);
        }

        $totalBermasalah = (clone $errorQuery)->count();

        // Additional financial aggregations
        $totalOrders = (clone $baseOrderQuery)->count();

        $totalOrderPayment = (clone $baseOrderQuery)
            ->where('order_status', '!=', 'CANCELLED')
            ->sum('total_payment');

        $totalIncome = (clone $baseOrderQuery)
            ->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
            ->sum('order_financials.total_income');

        return [
            'total_orders' => $totalOrders,
            'total_matched' => $totalMatched,
            'total_waiting_income' => $totalWaitingIncome,
            'total_selisih' => $totalSelisih,
            'total_waiting_order' => $totalBermasalah,
            'total_order_payment' => $totalOrderPayment,
            'total_income' => $totalIncome,
        ];
    }

    /**
     * Get paginated data based on matching status
     */
    public function getPaginatedData(string $tab, string $marketplaceCode, array $filters = [], int $perPage = 20)
    {
        $marketplaceAccountId = $filters['toko'] ?? null;
        $dateType = $filters['date_type'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $baseOrderQuery = Order::with('marketplaceAccount', 'financial')
            ->whereHas('marketplaceAccount.marketplace', function ($q) use ($marketplaceCode) {
                $q->where('code', $marketplaceCode);
            });

        if ($marketplaceAccountId) {
            $baseOrderQuery->where('marketplace_account_id', $marketplaceAccountId);
        }

        if ($dateType && $startDate && $endDate) {
            $start = $startDate.' 00:00:00';
            $end = $endDate.' 23:59:59';

            if ($dateType == 'order_date') {
                $baseOrderQuery->whereBetween('order_date', [$start, $end]);
            } elseif ($dateType == 'released_at') {
                $baseOrderQuery->whereHas('financial', function ($q) use ($start, $end) {
                    $q->whereBetween('released_at', [$start, $end]);
                });
            }
        }

        if ($tab == 'matched') {
            return (clone $baseOrderQuery)
                ->select('orders.*', 'order_financials.total_payment as financial_payment', 'order_financials.total_income as financial_income')
                ->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
                ->whereRaw('ABS(orders.total_payment - order_financials.total_payment) <= 0.01')
                ->orderBy('orders.order_date', 'desc')
                ->paginate($perPage);
        }

        if ($tab == 'waiting_income') {
            return (clone $baseOrderQuery)
                ->where('order_status', '!=', 'CANCELLED')
                ->whereDoesntHave('financial')
                ->orderBy('orders.order_date', 'desc')
                ->paginate($perPage);
        }

        if ($tab == 'needs_review') {
            return (clone $baseOrderQuery)
                ->select('orders.*', 'order_financials.total_payment as financial_payment', 'order_financials.total_income as financial_income')
                ->join('order_financials', 'orders.id', '=', 'order_financials.order_id')
                ->whereRaw('ABS(orders.total_payment - order_financials.total_payment) > 0.01')
                ->orderBy('orders.order_date', 'desc')
                ->paginate($perPage);
        }

        if ($tab == 'waiting_order') {
            $errorQuery = ImportError::with('importBatch.marketplaceAccount')
                ->where('error_type', 'order_not_found')
                ->whereHas('importBatch.marketplaceAccount.marketplace', function ($q) use ($marketplaceCode) {
                    $q->where('code', $marketplaceCode);
                });

            if ($marketplaceAccountId) {
                $errorQuery->whereHas('importBatch', function ($q) use ($marketplaceAccountId) {
                    $q->where('marketplace_account_id', $marketplaceAccountId);
                });
            }

            if ($dateType && $startDate && $endDate) {
                $start = $startDate.' 00:00:00';
                $end = $endDate.' 23:59:59';
                $errorQuery->whereBetween('created_at', [$start, $end]);
            }

            return (clone $errorQuery)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }

        return null;
    }
}
