@props(['order'])
@if($order->discount_amount > 0)
<p><b>Mã giảm giá:</b> {{ $order->coupon?->code ?? 'Đã áp dụng' }} · Giảm {{ number_format($order->discount_amount, 0, ',', '.') }}đ</p>
@endif
