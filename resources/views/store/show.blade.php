@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="container product-detail">
    <div class="product-gallery tone-{{ ($product->id % 5) + 1 }}"><span>{{ mb_substr($product->category?->name ?? 'M', 0, 1) }}</span><small>SẢN PHẨM CHỌN LỌC</small></div>
    <div class="product-info"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a> / <a href="{{ route('products.index') }}">Sản phẩm</a> / {{ $product->name }}</nav><div class="eyebrow">{{ $product->category?->name }} · {{ $product->sku }}</div><h1>{{ $product->name }}</h1><div class="rating">★★★★★ <span>4.8 · 126 đánh giá</span></div><div class="detail-price">{{ number_format($product->current_price, 0, ',', '.') }}đ @if($product->sale_price)<del>{{ number_format($product->selling_price, 0, ',', '.') }}đ</del>@endif</div><p class="lead">{{ $product->short_description }}</p><div class="stock {{ $product->stock > 0 ? '' : 'out' }}">{{ $product->stock > 0 ? 'Còn hàng · giao trong hôm nay' : 'Tạm hết hàng' }}</div><form class="buy-form" action="{{ route('cart.store', $product) }}" method="post">@csrf<label for="quantity">Số lượng</label><input id="quantity" type="number" name="quantity" value="1" min="1" max="99"><button class="button primary" @disabled($product->stock <= 0)>Thêm vào giỏ hàng</button></form><div class="detail-notes"><span>✓ Đổi trả trong 24 giờ</span><span>✓ Miễn phí giao từ 300.000đ</span></div></div>
</div>
@if($related->isNotEmpty())<section class="section container"><div class="section-heading"><h2>Có thể bạn cũng thích</h2></div><div class="product-grid">@foreach($related as $item)<x-product-card :product="$item" />@endforeach</div></section>@endif
@endsection
