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
        abort_unless($product->is_active, 404);
        $product->load('inventoryLots');
        $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:99']]);
        $cart = $request->session()->get('cart', []);
        $quantity = min(99, ($cart[$product->id] ?? 0) + (int) $request->input('quantity', 1));
        abort_if($quantity > $product->stock, 422, 'Số lượng yêu cầu vượt quá tồn kho.');
        $cart[$product->id] = $quantity;
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Đã thêm '.$product->name.' vào giỏ hàng.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $product->load('inventoryLots');
        abort_if($data['quantity'] > $product->stock, 422, 'Số lượng yêu cầu vượt quá tồn kho.');
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
        $products = Product::query()->with('inventoryLots.rescueCampaign')->whereIn('id', array_keys($cart))->get();
        $items = $products->map(function ($product) use ($cart) {
            $quantity = (int) $cart[$product->id];
            $lineTotal = $product->priceFor($quantity);

            return ['product' => $product, 'quantity' => $quantity, 'unit_price' => $lineTotal / max(1, $quantity), 'line_total' => $lineTotal];
        });

        return ['items' => $items, 'subtotal' => $items->sum('line_total')];
    }
}
