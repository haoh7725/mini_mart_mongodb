<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
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
        $orders = $this->ordersFor($request)
            ->withCount('items')->latest()->paginate(10);

        return view('account.orders.index', compact('orders'));
    }

    public function showOrder(Request $request, string $code)
    {
        $order = $this->ordersFor($request)->with(['items.review'])->where('code', $code)->firstOrFail();
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
            if (! $product || $product->stock < 1) { $skipped[] = $item->product_name; continue; }
            $current = (int) ($cart[$product->id] ?? 0);
            $cart[$product->id] = min(99, $current + (int) $item->quantity, (int) $product->stock);
            $added += max(0, $cart[$product->id] - $current);
        }
        $request->session()->put('cart', $cart);
        $message = "Đã thêm {$added} sản phẩm vào giỏ.".($skipped ? ' Bỏ qua: '.implode(', ', $skipped).'.' : '');
        return redirect()->route('cart.index')->with('success', $message);
    }

    private function ordersFor(Request $request)
    {
        return Order::query()->ownedBy($request->user());
    }
}
