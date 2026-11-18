<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Support\DocumentTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()->withCount('items')->with('returnRequest')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($x) => $x->where('code', 'like', '%'.$request->string('q').'%')->orWhere('customer_name', 'like', '%'.$request->string('q').'%')->orWhere('customer_phone', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $request->input('status') === 'return_pending'
                ? $q->whereHas('returnRequest', fn ($r) => $r->where('status', 'pending'))
                : $q->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'shipper', 'returnRequest', 'trackingEvents.creator']);

        return view('admin.orders.show', ['order' => $order, 'shippers' => $this->shippers(), 'manualStatuses' => self::manualStatuses()]);
    }

    public function update(Request $request, Order $order, OrderService $orders)
    {
        $shippers = $this->shippers();
        $data = $request->validate([
            'status' => ['required', Rule::in(self::manualStatuses())],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_LABELS))],
            'shipper_id' => ['nullable', Rule::in($shippers->modelKeys())],
        ]);
        if ($order->isClosed()) {
            return back()->withErrors(['status' => 'Đơn đã hủy hoặc đã trả hàng, không thể cập nhật thêm.']);
        }
        DocumentTransaction::run(function () use ($order, $data, $orders, $shippers) {
            $order->refresh();
            abort_if($order->isClosed(), 422, 'Đơn đã hủy hoặc đã trả hàng, không thể cập nhật thêm.');
            $shipper = $shippers->find($data['shipper_id'] ?? null);
            $newShipper = $shipper && $shipper->id !== $order->shipper_id;
            $order->update(['payment_status' => $data['payment_status'], 'shipper_id' => $shipper?->id]);
            if ($newShipper) {
                $orders->track($order, 'Đã giao cho shipper '.$shipper->name, $shipper->phone ? 'Số điện thoại shipper: '.$shipper->phone : null);
            }
            $orders->changeStatus($order, $data['status']);
        });

        return back()->with('success', 'Đã cập nhật đơn hàng.');
    }

    public function track(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $orders->track($order, $data['title'], $data['description'] ?? null);

        return back()->with('success', 'Đã thêm cập nhật hành trình.');
    }

    public function resolveReturn(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'admin_note' => ['nullable', 'string', 'max:500', 'required_if:decision,reject'],
        ], ['admin_note.required_if' => 'Vui lòng ghi lý do từ chối để khách hiểu.']);
        $done = $orders->resolveReturn($order, $data['decision'] === 'approve', $data['admin_note'] ?? null);

        return $done
            ? back()->with('success', $data['decision'] === 'approve' ? 'Đã chấp nhận trả hàng/hoàn tiền.' : 'Đã từ chối yêu cầu trả hàng.')
            : back()->withErrors(['return' => 'Yêu cầu trả hàng đã được xử lý trước đó.']);
    }

    private function shippers()
    {
        return User::query()->activeWithRole('shipper')->orderBy('name')->get();
    }

    // Trạng thái admin được chọn tay; "returned" chỉ có được khi duyệt yêu cầu trả hàng.
    private static function manualStatuses(): array
    {
        return array_keys(array_diff_key(Order::STATUS_LABELS, ['returned' => true]));
    }
}
