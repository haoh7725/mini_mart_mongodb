<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\RescueCampaign;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $monthlyRevenue = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = now()->subMonths($monthsAgo);
            return [
                'label' => 'T'.$month->format('m'),
                'value' => (float) Order::where('status', 'completed')->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->sum('total_amount'),
            ];
        });
        $orderStatuses = Order::query()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $lowStockProducts = Product::with('inventoryLots')->get()->filter(fn ($product) => $product->stock <= 10)->sortBy('stock')->take(6);

        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::count(), 'categories' => Category::count(),
                'orders' => Order::count(), 'customers' => User::whereHas('role', fn ($q) => $q->where('slug', 'customer'))->count(),
                'revenue' => Order::where('status', 'completed')->sum('total_amount'),
                'low_stock' => DB::table('inventory_lots')->select('product_id')->groupBy('product_id')->havingRaw('SUM(quantity - reserved_quantity) <= 10')->count(),
                'today_orders' => Order::whereDate('created_at', today())->count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'rescued' => RescueCampaign::sum('rescued_quantity'),
            ],
            'recentOrders' => Order::latest()->take(6)->get(),
            'monthlyRevenue' => $monthlyRevenue,
            'chartMax' => max(1, (float) $monthlyRevenue->max('value')),
            'orderStatuses' => $orderStatuses,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
