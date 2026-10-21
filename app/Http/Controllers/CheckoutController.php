<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Http\Requests\CheckoutRequest;
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

    public function store(CheckoutRequest $request)
    {
        $customer = $request->safe()->except('payment');
        $cart = $request->session()->get('cart', []);
        abort_if(empty($cart), 422, 'Giỏ hàng đang trống.');

        $order = DB::transaction(function () use ($cart, $customer) {
            $products = Product::query()->with(['inventoryLots' => fn ($q) => $q->orderByRaw('expires_at is null, expires_at asc')->lockForUpdate()])
                ->where('is_active', true)->whereIn('id', array_keys($cart))->lockForUpdate()->get();
            abort_if($products->count() !== count($cart), 422, 'Một sản phẩm trong giỏ không còn được bán.');
            foreach ($products as $product) {
                abort_if((int) $cart[$product->id] > $product->stock, 422, $product->name.' không còn đủ số lượng trong kho.');
            }
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
                $remaining = $quantity;
                foreach ($product->inventoryLots as $lot) {
                    $available = (float) $lot->quantity - (float) $lot->reserved_quantity;
                    $deduct = min($available, $remaining);
                    if ($deduct <= 0) continue;
                    $lot->decrement('quantity', $deduct);
                    DB::table('stock_movements')->insert([
                        'warehouse_id' => $lot->warehouse_id, 'product_id' => $product->id,
                        'product_variant_id' => $lot->product_variant_id, 'inventory_lot_id' => $lot->id,
                        'created_by' => auth()->id(), 'type' => 'sale', 'quantity' => -$deduct,
                        'reference_type' => Order::class, 'reference_id' => $order->id,
                        'note' => 'Xuất kho cho đơn '.$order->code, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $remaining -= $deduct;
                    if ($remaining <= 0) break;
                }
            }
            DB::table('payments')->insert([
                'order_id' => $order->id, 'method' => 'cod', 'amount' => $order->total_amount,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
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
