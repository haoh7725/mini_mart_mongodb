<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::count(), 'categories' => Category::count(),
                'orders' => Order::count(), 'customers' => User::whereHas('role', fn ($q) => $q->where('slug', 'customer'))->count(),
                'revenue' => Order::where('status', 'completed')->sum('total_amount'),
                'low_stock' => DB::table('inventory_lots')->select('product_id')->groupBy('product_id')->havingRaw('SUM(quantity - reserved_quantity) <= 10')->count(),
            ],
            'recentOrders' => Order::latest()->take(6)->get(),
        ]);
    }
}
