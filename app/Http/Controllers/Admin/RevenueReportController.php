<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = Carbon::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();
        $start = $month->copy();
        $end = $month->copy()->addMonth();

        $orders = DB::table('orders')
            ->where('status', 'completed')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        $summary = (clone $orders)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        $dailyRevenue = (clone $orders)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as order_count, SUM(total_amount) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.created_at', '<', $end);

        $reviewCounts = DB::table('reviews')
            ->select('order_item_id')
            ->selectRaw('COUNT(*) as review_count')
            ->groupBy('order_item_id');
        $stock = DB::table('inventory_lots')
            ->select('product_id')
            ->selectRaw('SUM(quantity - reserved_quantity) as available_stock')
            ->groupBy('product_id');

        $productSales = fn () => (clone $sales)
            ->leftJoinSub($reviewCounts, 'review_counts', fn ($join) => $join->on('review_counts.order_item_id', '=', 'order_items.id'))
            ->leftJoinSub($stock, 'stock_totals', fn ($join) => $join->on('stock_totals.product_id', '=', 'order_items.product_id'))
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->select('order_items.product_id', 'order_items.product_name', 'order_items.sku')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold, SUM(order_items.line_total) as item_revenue')
            ->selectRaw('COUNT(DISTINCT order_items.id) as sold_lines, COUNT(DISTINCT CASE WHEN review_counts.review_count > 0 THEN order_items.id END) as reviewed_lines')
            ->selectRaw('COALESCE(stock_totals.available_stock, 0) as available_stock, units.symbol as unit_symbol')
            ->selectRaw('CASE WHEN COUNT(DISTINCT order_items.id) = 0 THEN 0 ELSE COUNT(DISTINCT CASE WHEN review_counts.review_count > 0 THEN order_items.id END) * 100.0 / COUNT(DISTINCT order_items.id) END as response_rate')
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku', 'stock_totals.available_stock', 'units.symbol');

        $topProducts = $productSales()
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        $lowestRevenueProduct = $productSales()
            ->orderBy('item_revenue')
            ->first();

        $categoryRevenue = (clone $sales)
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("COALESCE(categories.name, 'Chưa phân loại') as category_name")
            ->selectRaw('SUM(order_items.quantity) as quantity_sold, SUM(order_items.line_total) as item_revenue')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('item_revenue')
            ->get();

        $monthlyRevenue = collect(range(11, 0))->map(function (int $monthsAgo) {
            $period = now()->startOfMonth()->subMonths($monthsAgo);
            return [
                'label' => $period->format('m/Y'),
                'revenue' => (float) DB::table('orders')->where('status', 'completed')
                    ->whereYear('created_at', $period->year)->whereMonth('created_at', $period->month)
                    ->sum('total_amount'),
            ];
        });

        return view('admin.reports.revenue', [
            'month' => $month,
            'summary' => $summary,
            'dailyRevenue' => $dailyRevenue,
            'topProducts' => $topProducts,
            'lowestRevenueProduct' => $lowestRevenueProduct,
            'categoryRevenue' => $categoryRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'chartMax' => max(1, (float) $monthlyRevenue->max('revenue')),
        ]);
    }
}
