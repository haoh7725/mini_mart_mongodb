@extends('layouts.app')
@section('title', $success ? 'Thanh toán thành công' : 'Thanh toán chưa thành công')
@section('content')
<section class="success-page container">
    <div class="success-check">{{ $success ? '✓' : '!' }}</div>
    <span class="eyebrow">Kết quả thanh toán VNPay</span>
    <h1>{{ $success ? 'Thanh toán thành công!' : 'Thanh toán chưa thành công' }}</h1>
    <p>{{ $message }}</p>
    @if($order)
        <div class="success-order">
            <span>Mã đơn hàng</span><strong>{{ $order->code }}</strong>
            <span>Tổng thanh toán</span><strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong>
            <span>Trạng thái</span><strong>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</strong>
        </div>
    @endif
    <a class="button primary" href="{{ route('products.index') }}">Tiếp tục mua sắm</a>
</section>
@endsection
