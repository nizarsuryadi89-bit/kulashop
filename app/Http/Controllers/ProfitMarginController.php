<?php

namespace App\Http\Controllers;

use App\Services\ProfitService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProfitMarginController extends Controller
{
    protected $profitService;

    public function __construct(ProfitService $profitService)
    {
        $this->profitService = $profitService;
    }

    public function index(Request $request)
    {
        $period = $request->input('period', 'this_month');
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
                $startDate = Carbon::today()->subDays(6);
                $endDate = Carbon::now();
                break;
            case '30_days':
                $startDate = Carbon::today()->subDays(29);
                $endDate = Carbon::now();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
                $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
                break;
            case 'this_month':
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfDay();
                break;
        }

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_status' => $request->input('order_status', 'COMPLETED'),
            'sku' => $request->input('sku', ''),
        ];

        $summary = $this->profitService->getProfitSummary($filters);
        $productMetrics = $this->profitService->getProductProfit($filters);
        $missingHppOrders = $this->profitService->getOrdersMissingHpp($filters);

        return view('report.profit_margin', compact(
            'summary',
            'productMetrics',
            'missingHppOrders',
            'filters',
            'period'
        ));
    }
}
