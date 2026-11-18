<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RevenueReportController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = Carbon::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();
        $report = $reports->forMonth($month);
        $monthlyRevenue = $reports->monthlyRevenue(12);

        return view('admin.reports.revenue', $report + [
            'month' => $month, 'topProducts' => $report['itemSales']->take(10),
            'lowestRevenueProduct' => $report['itemSales']->sortBy('item_revenue')->first(),
            'monthlyRevenue' => $monthlyRevenue, 'chartMax' => max(1, (float) $monthlyRevenue->max('revenue')),
        ]);
    }
}
