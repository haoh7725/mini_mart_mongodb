<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function create(Request $request, CartController $cart)
    {
        $data = $cart->cartData($request);
        return $data['items']->isEmpty() ? redirect()->route('cart.index') : view('store.checkout', $data);
    }

    public function store(Request $request)
    {
        $customer = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ]);
        $cart = $request->session()->get('cart', []);
        abort_if(empty($cart), 422, 'Giỏ hàng đang trống.');

        $order = DB::transaction(function () use ($cart, $customer) {
            $products = Product::query()->with('inventoryLots')->whereIn('id', array_keys($cart))->lockForUpdate()->get();
            $subtotal = $products->sum(fn ($product) => $product->current_price * $cart[$product->id]);
            $shippingFee = $subtotal >= 300000 ? 0 : 25000;
            $order = Order::query()->create($customer + [
                'code' => 'MM-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $subtotal + $shippingFee,
            ]);
            foreach ($products as $product) {
                $quantity = (int) $cart[$product->id];
                $order->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                    'quantity' => $quantity, 'unit_price' => $product->current_price, 'line_total' => $product->current_price * $quantity,
                ]);
            }
            return $order;
        });

        $request->session()->forget('cart');
        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order)
    {
        return view('store.success', compact('order'));
    }
}
