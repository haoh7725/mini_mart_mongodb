<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Http\Requests\CheckoutRequest;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function create(Request $request, CartController $cart)
    {
        $data = $cart->cartData($request);
        $data['customer'] = $request->user();
        return $data['items']->isEmpty() ? redirect()->route('cart.index') : view('store.checkout', $data);
    }

    public function store(CheckoutRequest $request, VnpayService $vnpay)
    {
        $customer = $request->safe()->only([
            'customer_name',
            'customer_phone',
            'customer_email',
            'shipping_address',
            'customer_note',
        ]);
        $paymentMethod = $request->string('payment')->toString();
        if ($paymentMethod === 'vnpay' && ! $vnpay->configured()) {
            return back()->withInput()->withErrors(['payment' => 'VNPay chưa được cấu hình. Vui lòng nhập VNPAY_TMN_CODE và VNPAY_HASH_SECRET trong file .env.']);
        }
        $cart = $request->session()->get('cart', []);
        abort_if(empty($cart), 422, 'Giỏ hàng đang trống.');

        $order = DB::transaction(function () use ($cart, $customer, $paymentMethod) {
            $products = Product::query()->with([
                'inventoryLots' => fn ($q) => $q->lockForUpdate(),
                'inventoryLots.rescueCampaign' => fn ($q) => $q->lockForUpdate(),
            ])->where('is_active', true)->whereIn('id', array_keys($cart))->lockForUpdate()->get();
            abort_if($products->count() !== count($cart), 422, 'Một sản phẩm trong giỏ không còn được bán.');
            foreach ($products as $product) {
                abort_if((int) $cart[$product->id] > $product->stock, 422, $product->name.' không còn đủ số lượng trong kho.');
            }
            $allocations = $products->mapWithKeys(fn ($product) => [$product->id => $product->allocate((int) $cart[$product->id])]);
            $lineTotals = $allocations->map(fn (array $parts) => collect($parts)->sum(fn ($part) => $part['quantity'] * $part['unit_price']));
            $subtotal = $lineTotals->sum();
            $shippingFee = $subtotal >= 300000 ? 0 : 25000;
            $order = Order::query()->create($customer + [
                'user_id' => auth()->id(),
                'code' => 'MM-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $subtotal + $shippingFee,
            ]);
            foreach ($products as $product) {
                $quantity = (int) $cart[$product->id];
                $lineTotal = $lineTotals[$product->id];
                $order->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                    'quantity' => $quantity, 'unit_price' => round($lineTotal / $quantity, 2), 'line_total' => $lineTotal,
                ]);
                foreach ($allocations[$product->id] as $part) {
                    $lot = $part['lot'];
                    $lot->decrement('quantity', $part['quantity']);
                    $part['campaign']?->increment('rescued_quantity', $part['quantity']);
                    DB::table('stock_movements')->insert([
                        'warehouse_id' => $lot->warehouse_id, 'product_id' => $product->id,
                        'product_variant_id' => $lot->product_variant_id, 'inventory_lot_id' => $lot->id,
                        'rescue_campaign_id' => $part['campaign']?->id,
                        'created_by' => auth()->id(), 'type' => 'sale', 'quantity' => -$part['quantity'],
                        'reference_type' => Order::class, 'reference_id' => $order->id,
                        'note' => 'Xuất kho cho đơn '.$order->code, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            DB::table('payments')->insert([
                'order_id' => $order->id, 'method' => $paymentMethod === 'vnpay' ? 'online' : 'cod', 'amount' => $order->total_amount,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $order;
        });

        $request->session()->forget('cart');
        if ($paymentMethod === 'vnpay') {
            return redirect()->away($vnpay->paymentUrl($order, $request));
        }

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order)
    {
        $payment = DB::table('payments')->where('order_id', $order->id)->latest('id')->first();

        return view('store.success', compact('order', 'payment'));
    }
}
