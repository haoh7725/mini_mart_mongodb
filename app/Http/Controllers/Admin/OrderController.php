<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()->withCount('items')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($x) => $x->where('code', 'like', '%'.$request->string('q').'%')->orWhere('customer_name', 'like', '%'.$request->string('q').'%')->orWhere('customer_phone', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }
    public function show(Order $order) { $order->load('items'); return view('admin.orders.show', compact('order')); }
    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,confirmed,processing,shipping,completed,cancelled'], 'payment_status' => ['required', 'in:unpaid,paid,failed,refunded']]);
        $order->update($data);
        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }
}
