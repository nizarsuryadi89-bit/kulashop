<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReportController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->input('year', date('Y'));
        $yearExpression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%Y', order_date) AS INTEGER)" : 'YEAR(order_date)';
        $monthExpression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', order_date) AS INTEGER)" : 'MONTH(order_date)';

        $availableYears = PurchaseOrder::selectRaw($yearExpression.' as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        // Laporan per bulan dalam tahun tersebut
        $monthlyReport = PurchaseOrder::select(
            DB::raw($monthExpression.' as month'),
            DB::raw('COUNT(id) as total_orders'),
            DB::raw('SUM(grand_total) as total_amount'),
            DB::raw('SUM(paid_amount) as total_paid'),
            DB::raw('SUM(grand_total - paid_amount) as total_outstanding')
        )
            ->whereYear('order_date', $year)
            ->whereNotIn('status', ['cancelled'])
            ->groupBy(DB::raw($monthExpression))
            ->get()
            ->keyBy('month');

        $reportData = [];
        for ($i = 1; $i <= 12; $i++) {
            $reportData[$i] = [
                'month' => date('F', mktime(0, 0, 0, $i, 1)),
                'total_orders' => $monthlyReport->has($i) ? $monthlyReport[$i]->total_orders : 0,
                'total_amount' => $monthlyReport->has($i) ? $monthlyReport[$i]->total_amount : 0,
                'total_paid' => $monthlyReport->has($i) ? $monthlyReport[$i]->total_paid : 0,
                'total_outstanding' => $monthlyReport->has($i) ? $monthlyReport[$i]->total_outstanding : 0,
            ];
        }

        $summary = [
            'total_orders' => collect($reportData)->sum('total_orders'),
            'total_amount' => collect($reportData)->sum('total_amount'),
            'total_paid' => collect($reportData)->sum('total_paid'),
            'total_outstanding' => collect($reportData)->sum('total_outstanding'),
        ];

        return view('purchase.report.index', compact('year', 'availableYears', 'reportData', 'summary'));
    }
}
