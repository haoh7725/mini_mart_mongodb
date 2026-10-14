<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return view('store.cart', $this->cartData($request));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:99']]);
        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = min(99, ($cart[$product->id] ?? 0) + (int) $request->input('quantity', 1));
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Đã thêm '.$product->name.' vào giỏ hàng.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = $data['quantity'];
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function cartData(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::query()->with('inventoryLots')->whereIn('id', array_keys($cart))->get();
        $items = $products->map(fn ($product) => [
            'product' => $product,
            'quantity' => (int) $cart[$product->id],
            'line_total' => $product->current_price * $cart[$product->id],
        ]);

        return ['items' => $items, 'subtotal' => $items->sum('line_total')];
    }
}
