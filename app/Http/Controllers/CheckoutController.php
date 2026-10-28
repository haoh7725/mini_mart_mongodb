<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        $couponCode = trim((string) $request->input('coupon_code', ''));
        $order = DB::transaction(function () use ($cart, $customer, $paymentMethod, $couponCode) {
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
            $shippingFee = Order::shippingFeeFor($subtotal);
            // Match cancellation's stock-before-coupon locking order.
            $coupon = $couponCode !== '' ? Coupon::forCode($couponCode, true) : null;
            $discount = $coupon?->discount($subtotal) ?? 0;
            if ($paymentMethod === 'vnpay' && $subtotal - $discount + $shippingFee <= 0) {
                throw ValidationException::withMessages(['payment' => 'Đơn hàng 0đ không cần thanh toán trực tuyến. Vui lòng chọn COD để xác nhận đơn.']);
            }
            $order = Order::query()->create($customer + [
                'user_id' => auth()->id(),
                'code' => 'MM-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'coupon_id' => $coupon?->id,
                'discount_amount' => $discount,
                'total_amount' => $subtotal - $discount + $shippingFee,
            ]);
            $coupon?->increment('used_count');
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
            app(OrderService::class)->placed($order, $paymentMethod);

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

    public function previewCoupon(Request $request, CartController $cart)
    {
        $data = $request->validate(['coupon_code' => ['required', 'string', 'max:50']]);
        $cartData = $cart->cartData($request);
        abort_if($cartData['items']->isEmpty(), 422, 'Giỏ hàng đang trống.');
        $subtotal = $cartData['subtotal'];
        $coupon = Coupon::forCode($data['coupon_code']);
        $discount = $coupon->discount($subtotal);

        return response()->json(['discount' => $discount, 'total' => $subtotal - $discount + $cartData['shipping_fee']]);
    }
}
