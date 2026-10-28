<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function profile(Request $request)
    {
        return view('account.profile', [
            'user' => $request->user(),
            'greenPoints' => $request->user()->green_points,
            'pointHistory' => $request->user()->greenPointTransactions()->latest()->take(5)->get(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ], [
            'current_password.current_password' => 'Mật khẩu hiện tại không chính xác.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        $user->fill(collect($data)->only(['name', 'email', 'phone'])->all());
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    public function orders(Request $request)
    {
        $tab = array_key_exists((string) $request->query('tab'), Order::CUSTOMER_TABS) ? $request->query('tab') : 'all';
        $counts = collect(Order::CUSTOMER_TABS)->except('all')
            ->map(fn ($label, $key) => $this->ordersFor($request)->forCustomerTab($key)->count());
        $orders = $this->ordersFor($request)->forCustomerTab($tab)
            ->with(['items' => fn ($items) => $items->limit(2), 'returnRequest'])->withCount('items')->latest()->paginate(10)->withQueryString();

        return view('account.orders.index', compact('orders', 'tab', 'counts'));
    }

    public function showOrder(Request $request, string $code)
    {
        $order = $this->ordersFor($request)->with(['items.review', 'shipper', 'returnRequest', 'trackingEvents'])->where('code', $code)->firstOrFail();
        $payment = DB::table('payments')->where('order_id', $order->id)->latest('id')->first();

        return view('account.orders.show', compact('order', 'payment'));
    }

    public function reorder(Request $request, string $code)
    {
        $order = $this->ordersFor($request)->with('items')->where('code', $code)->firstOrFail();
        $products = Product::with('inventoryLots')->whereIn('id', $order->items->pluck('product_id')->filter())->where('is_active', true)->get()->keyBy('id');
        $cart = $request->session()->get('cart', []);
        $added = 0;
        $skipped = [];
        foreach ($order->items as $item) {
            $product = $products->get($item->product_id);
            if (! $product || $product->stock < 1) {
                $skipped[] = $item->product_name;

                continue;
            }
            $current = (int) ($cart[$product->id] ?? 0);
            $cart[$product->id] = min(99, $current + (int) $item->quantity, (int) $product->stock);
            $added += max(0, $cart[$product->id] - $current);
        }
        $request->session()->put('cart', $cart);
        $message = "Đã thêm {$added} sản phẩm vào giỏ.".($skipped ? ' Bỏ qua: '.implode(', ', $skipped).'.' : '');

        return redirect()->route('cart.index')->with('success', $message);
    }

    public function cancelOrder(Request $request, string $code, OrderService $orders)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(Order::CANCEL_REASONS)],
            'reason_detail' => ['nullable', 'string', 'max:200'],
        ], ['reason.required' => 'Vui lòng chọn lý do hủy đơn.']);
        $order = $this->ordersFor($request)->where('code', $code)->firstOrFail();

        $reason = $data['reason'].(filled($data['reason_detail'] ?? null) ? ': '.$data['reason_detail'] : '');
        $cancelled = $orders->cancelByCustomer($order, $reason);

        return $cancelled
            ? redirect()->route('account.orders.show', $code)->with('success', 'Đã hủy đơn hàng '.$code.'.')
            : back()->withErrors(['order' => 'Đơn hàng này không thể hủy nữa. Vui lòng liên hệ hotline 1900 6868.']);
    }

    public function requestReturn(Request $request, string $code, OrderService $orders)
    {
        $data = $request->validate([
            'return_reason' => ['required', Rule::in(ReturnRequest::REASONS)],
            'return_detail' => ['nullable', 'string', 'max:500'],
        ], ['return_reason.required' => 'Vui lòng chọn lý do trả hàng.']);
        $order = $this->ordersFor($request)->where('code', $code)->firstOrFail();

        $sent = DB::transaction(function () use ($order, $data, $orders) {
            $locked = Order::query()->with('returnRequest')->lockForUpdate()->findOrFail($order->id);
            if (! $locked->canRequestReturn()) {
                return false;
            }
            $orders->requestReturn($locked, $data['return_reason'], $data['return_detail'] ?? null);

            return true;
        });

        return $sent
            ? redirect()->route('account.orders.show', $code)->with('success', 'Đã gửi yêu cầu trả hàng/hoàn tiền. Shop sẽ phản hồi sớm.')
            : back()->withErrors(['return_reason' => 'Đơn này không thể yêu cầu trả hàng (đã quá '.Order::RETURN_WINDOW_DAYS.' ngày hoặc đã gửi yêu cầu).']);
    }

    private function ordersFor(Request $request)
    {
        return Order::query()->ownedBy($request->user());
    }
}
