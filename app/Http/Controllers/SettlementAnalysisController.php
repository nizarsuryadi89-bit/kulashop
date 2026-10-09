<?php

namespace App\Http\Controllers;

use App\Models\OrderFinancial;
use App\Models\MarketplaceAccount;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SettlementAnalysisController extends Controller
{
    /**
     * Display a summary of settlements grouped by release_date.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = OrderFinancial::whereNotNull('released_at');

        // Filter by release date range
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        if ($startDate) {
            $query->whereDate('released_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('released_at', '<=', $endDate);
        }

        $settlements = $query->selectRaw('
                DATE(released_at) as release_date,
                COUNT(*) as total_orders,
                SUM(product_amount) as total_sales,
                SUM(admin_fee + transaction_fee + service_fee + promotion_fee + affiliate_fee + other_fee) as total_fees,
                SUM(settlement_amount) as total_settlement
            ')
            ->groupBy('release_date')
            ->orderBy('release_date', 'desc')
            ->get();

        return view('reconciliation.settlement', compact('settlements', 'startDate', 'endDate'));
    }

    /**
     * Show the detailed orders for a specific settlement date.
     */
    public function show(Request $request, $date)
    {
        $user = auth()->user();
        
        $financials = OrderFinancial::with('order.marketplaceAccount.marketplace')
            ->whereDate('released_at', $date)
            ->get();

        return response()->json([
            'date' => Carbon::parse($date)->format('d M Y'),
            'data' => $financials->map(function($fin) {
                return [
                    'order_id' => $fin->order->external_order_id,
                    'marketplace' => $fin->order->marketplaceAccount->marketplace->name ?? '-',
                    'store' => $fin->order->marketplaceAccount->store_name ?? '-',
                    'product_amount' => $fin->product_amount,
                    'total_fees' => $fin->admin_fee + $fin->transaction_fee + $fin->service_fee + $fin->promotion_fee + $fin->affiliate_fee + $fin->other_fee,
                    'settlement_amount' => $fin->settlement_amount,
                ];
            })
        ]);
    }
}
