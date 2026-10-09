<?php

namespace App\Http\Controllers;

use App\Models\ImportError;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Services\OrderIncomeReconciliationService;
use Illuminate\Http\Request;

class ShopeeReconciliationController extends Controller
{
    protected $reconciliationService;

    public function __construct(OrderIncomeReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'matched');
        $toko = $request->filled('toko') ? (int) $request->toko : null;

        $filters = [
            'toko' => $toko,
            'date_type' => $request->get('date_type'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        // Get Summary Metrics
        $summary = $this->reconciliationService->getSummary('shopee', $filters);

        // Get Paginated Data
        $data = $this->reconciliationService->getPaginatedData($tab, 'shopee', $filters);

        if ($data) {
            $data->appends($request->all());
        }

        $accounts = MarketplaceAccount::whereHas('marketplace', function ($q) {
            $q->where('code', 'shopee');
        })->where('status', 'active')->get();

        return view('reconciliation.shopee', [
            'tab' => $tab,
            'accounts' => $accounts,
            'data' => $data,
            'totalMatched' => $summary['total_matched'],
            'totalWaitingIncome' => $summary['total_waiting_income'],
            'totalSelisih' => $summary['total_selisih'],
            'totalBermasalah' => $summary['total_waiting_order'],
            'totalOrders' => $summary['total_orders'],
            'totalOrderPayment' => $summary['total_order_payment'],
            'totalIncome' => $summary['total_income'],
        ]);
    }

    public function show($id, Request $request)
    {
        $type = $request->get('type', 'order');
        $tab = $request->get('tab');

        if ($type == 'error') {
            $error = ImportError::with('batch.marketplaceAccount')->findOrFail($id);

            return view('reconciliation.shopee_show_error', compact('error', 'tab'));
        }

        $order = Order::with(['items.product', 'financial', 'customer', 'marketplaceAccount'])
            ->findOrFail($id);

        return view('reconciliation.shopee_show', compact('order', 'tab'));
    }
}
