@extends('layouts.app')
@section('title', 'Giỏ hàng')
@section('content')
<section class="page-hero compact"><div class="container"><span class="eyebrow">Bước 1 / 2</span><h1>Giỏ hàng của bạn</h1></div></section>
<div class="container cart-layout">
    <section>
        @forelse($items as $item)
        <article class="cart-item"><a class="cart-thumb tone-{{ (crc32((string) $item['product']->id) % 5) + 1 }}" href="{{ route('products.show', $item['product']) }}" aria-label="Xem {{ $item['product']->name }}">@if($item['product']->primary_image_url)<img src="{{ $item['product']->primary_image_url }}" alt="{{ $item['product']->name }}" loading="lazy">@else{{ mb_substr($item['product']->category?->name ?? 'M', 0, 1) }}@endif</a><div class="cart-name"><small>{{ $item['product']->category?->name }}</small><h3><a href="{{ route('products.show', $item['product']) }}">{{ $item['product']->name }}</a></h3><p>{{ number_format($item['unit_price'], 0, ',', '.') }}đ / sản phẩm</p></div><form class="quantity-form" action="{{ route('cart.update', $item['product']) }}" method="post">@csrf @method('PATCH')<label class="sr-only" for="qty-{{ $item['product']->id }}">Số lượng</label><input id="qty-{{ $item['product']->id }}" name="quantity" type="number" min="1" max="99" value="{{ $item['quantity'] }}"><button>Cập nhật</button></form><strong class="line-total">{{ number_format($item['line_total'], 0, ',', '.') }}đ</strong><form action="{{ route('cart.destroy', $item['product']) }}" method="post">@csrf @method('DELETE')<button class="remove" aria-label="Xóa {{ $item['product']->name }}">×</button></form></article>
        @empty
        <div class="empty-state"><div class="empty-icon">M</div><h2>Giỏ hàng đang trống</h2><p>Hàng trăm sản phẩm tươi ngon đang chờ bạn khám phá.</p><a class="button primary" href="{{ route('products.index') }}">Bắt đầu mua sắm</a></div>
        @endforelse
    </section>
    @if($items->isNotEmpty())<aside class="order-summary"><h2>Tóm tắt đơn hàng</h2><div><span>Tạm tính</span><strong>{{ number_format($subtotal, 0, ',', '.') }}đ</strong></div><div><span>Phí giao hàng</span><strong>{{ $shipping_fee ? number_format($shipping_fee, 0, ',', '.').'đ' : 'Miễn phí' }}</strong></div>@if($shipping_fee)<p>Mua thêm {{ number_format(\App\Models\Order::FREE_SHIPPING_THRESHOLD - $subtotal, 0, ',', '.') }}đ để được miễn phí giao hàng.</p>@endif<hr><div class="summary-total"><span>Tổng cộng</span><strong>{{ number_format($subtotal + $shipping_fee, 0, ',', '.') }}đ</strong></div><a class="button primary full" href="{{ route('checkout.create') }}">Tiến hành thanh toán</a><a class="continue" href="{{ route('products.index') }}">← Tiếp tục mua sắm</a></aside>@endif
</div>
@endsection
