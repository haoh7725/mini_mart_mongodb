<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = Carbon::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();
        $start = $month->copy();
        $end = $month->copy()->addMonth();

        $completedOrders = DB::table('orders')
            ->where('status', 'completed')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        $summary = (clone $completedOrders)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        // Use the order item snapshots so historical reports remain accurate after product edits.
        $itemSales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.created_at', '<', $end)
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.sku',
            ])
            ->selectRaw('SUM(order_items.quantity) as quantity_sold, SUM(order_items.line_total) as item_revenue, COUNT(DISTINCT orders.id) as order_count')
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku')
            ->orderByDesc('quantity_sold')
            ->get();

        $stock = DB::table('inventory_lots')
            ->select('product_id')
            ->selectRaw('SUM(quantity - reserved_quantity) as available_stock')
            ->groupBy('product_id');

        $stockProducts = DB::table('products')
            ->leftJoinSub($stock, 'stock_totals', fn ($join) => $join->on('stock_totals.product_id', '=', 'products.id'))
            ->whereNull('products.deleted_at')
            ->where('products.is_active', true)
            ->select('products.id', 'products.name', 'products.sku', 'products.unit_id')
            ->selectRaw('COALESCE(stock_totals.available_stock, 0) as available_stock')
            ->orderByDesc('available_stock')
            ->orderBy('products.name')
            ->limit(20)
            ->get();

        $units = DB::table('units')->whereIn('id', $stockProducts->pluck('unit_id'))->pluck('symbol', 'id');

        return view('admin.reports.index', [
            'month' => $month,
            'summary' => $summary,
            'itemSales' => $itemSales,
            'bestSellers' => $itemSales->take(10),
            'stockProducts' => $stockProducts,
            'units' => $units,
        ]);
    }
}
