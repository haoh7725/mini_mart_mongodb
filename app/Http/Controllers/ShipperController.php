<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShipperController extends Controller
{
    public const TABS = ['pickup' => 'Cần lấy hàng', 'shipping' => 'Đang giao', 'done' => 'Đã giao hôm nay'];

    public const FAIL_REASONS = [
        'Không liên lạc được khách',
        'Khách hẹn giao lại',
        'Sai hoặc thiếu địa chỉ',
        'Khách từ chối nhận hàng',
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'pickup';
        $mine = fn () => Order::query()->where('shipper_id', $request->user()->id);
        $scopes = [
            'pickup' => fn ($q) => $q->forCustomerTab('pickup'),
            'shipping' => fn ($q) => $q->forCustomerTab('shipping'),
            'done' => fn ($q) => $q->where('status', 'completed')->whereDate('delivered_at', today()),
        ];
        $counts = collect($scopes)->map(fn ($scope) => $scope($mine())->count());
        $orders = $scopes[$tab]($mine())->with(['items', 'latestTrackingEvent'])
            ->orderBy('estimated_delivery_at')->oldest()->get();
        $codAmounts = DB::table('payments')->whereIn('order_id', $orders->pluck('id'))->where('method', 'cod')
            ->where('status', 'pending')->pluck('amount', 'order_id');

        return view('shipper.index', compact('orders', 'tab', 'counts', 'codAmounts'));
    }

    public function pickup(Request $request, Order $order, OrderService $orders)
    {
        $this->authorizeOrder($request, $order, Order::AWAITING_PICKUP);
        $orders->changeStatus($order, 'shipping');

        return back()->with('success', 'Đã lấy hàng đơn '.$order->code.'. Chúc bạn giao hàng thuận lợi!');
    }

    public function update(Request $request, Order $order, OrderService $orders)
    {
        $this->authorizeOrder($request, $order, ['shipping']);
        $data = $request->validate(['note' => ['required', 'string', 'max:200']], ['note.required' => 'Vui lòng nhập vị trí hoặc ghi chú.']);
        $orders->track($order, 'Shipper cập nhật hành trình', $data['note']);

        return back()->with('success', 'Đã gửi cập nhật cho khách.');
    }

    public function deliver(Request $request, Order $order, OrderService $orders)
    {
        $this->authorizeOrder($request, $order, ['shipping']);
        $orders->changeStatus($order, 'completed');

        return back()->with('success', 'Đã giao thành công đơn '.$order->code.'.');
    }

    public function fail(Request $request, Order $order, OrderService $orders)
    {
        $this->authorizeOrder($request, $order, ['shipping']);
        $data = $request->validate(['reason' => ['required', Rule::in(array_keys(self::FAIL_REASONS))]]);
        $orders->deliveryFailed($order, self::FAIL_REASONS[$data['reason']]);

        return back()->with('success', 'Đã ghi nhận giao chưa thành công cho đơn '.$order->code.'.');
    }

    private function authorizeOrder(Request $request, Order $order, array $statuses): void
    {
        abort_unless((int) $order->shipper_id === (int) $request->user()->id, 403, 'Đơn này không được giao cho bạn.');
        abort_unless(in_array($order->status, $statuses, true), 422, 'Đơn '.$order->code.' đang ở trạng thái "'.$order->statusLabel().'", không thể thực hiện thao tác này.');
    }
}
