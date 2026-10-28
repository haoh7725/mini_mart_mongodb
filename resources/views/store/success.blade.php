@extends('layouts.app')
@section('title', 'Đặt hàng thành công')
@section('content')
<div class="container"><x-order-discount :order="$order" /></div>
<section class="success-page container"><div class="success-check">✓</div><span class="eyebrow">Đặt hàng thành công</span><h1>Cảm ơn bạn đã chọn MiniMart!</h1><p>Đơn hàng <b>{{ $order->code }}</b> đã được tiếp nhận. Chúng tôi sẽ liên hệ xác nhận và giao hàng sớm nhất.</p><div class="success-order"><span>Tổng thanh toán</span><strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong><span>Phương thức</span><strong>{{ ($payment->method ?? 'cod') === 'online' ? 'VNPay' : 'COD' }}</strong><span>Trạng thái</span><strong>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ xác nhận' }}</strong></div><a class="button primary" href="{{ route('products.index') }}">Tiếp tục mua sắm</a></section>
@endsection
