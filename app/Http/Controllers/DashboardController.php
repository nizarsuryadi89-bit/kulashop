<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\OwnerDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(OwnerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $filters = [
            'period' => $request->input('period', 'this_month'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'marketplace_account_id' => $request->input('marketplace_account_id', 'all'),
            'order_status' => $request->input('order_status', 'all'),
        ];

        $metrics = $this->dashboardService->getDashboardMetrics($filters);

        $marketplaceAccounts = DB::table('marketplace_accounts')
            ->join('marketplaces', 'marketplace_accounts.marketplace_id', '=', 'marketplaces.id')
            ->select('marketplace_accounts.id', 'marketplace_accounts.account_name', 'marketplaces.name as marketplace_name')
            ->get();

        // Get dynamic statuses available in the database
        $dbStatuses = DB::table('orders')->select('order_status')->whereNotNull('order_status')->distinct()->pluck('order_status');
        $statusMap = [
            'COMPLETED' => 'Selesai',
            'CANCELLED' => 'Dibatalkan',
            'SHIPPED' => 'Dikirim',
            'PROCESSING' => 'Diproses',
            'RETURNED' => 'Pengembalian',
        ];

        $availableStatuses = [];
        foreach ($dbStatuses as $status) {
            $availableStatuses[$status] = $statusMap[$status] ?? Str::title(str_replace('_', ' ', $status));
        }

        // Merge dropdown data with metrics from service
        $viewData = array_merge($metrics, [
            'marketplaceAccountId' => $filters['marketplace_account_id'],
            'orderStatus' => $filters['order_status'],
            'marketplaceAccounts' => $marketplaceAccounts,
            'availableStatuses' => $availableStatuses,
        ]);

        return view('dashboard', $viewData);
    }
}
