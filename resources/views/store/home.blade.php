@extends('layouts.app')
@section('title', 'Trang chủ')
@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <div class="eyebrow">Thực phẩm sạch · Nguồn gốc rõ ràng</div>
            <h1>Tươi ngon mỗi ngày,<br><em>giao nhanh tận cửa.</em></h1>
            <p>Từ rau củ vừa thu hoạch đến nhu yếu phẩm gia đình, MiniMart giúp bạn mua sắm nhanh hơn và an tâm hơn.</p>
            <div class="hero-actions"><a class="button primary" href="{{ route('products.index') }}">Mua sắm ngay</a><a class="button ghost" href="#featured">Xem ưu đãi</a></div>
            <div class="trust-row"><span><b>10K+</b> khách hàng</span><span><b>500+</b> sản phẩm</span><span><b>4.9/5</b> hài lòng</span></div>
        </div>
        <div class="hero-card" aria-label="Ưu đãi trong ngày">
            <div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div>
            <span class="hero-label">COMBO XANH</span><strong>299K</strong><p>Trọn tuần tươi ngon<br>cho cả gia đình</p><a href="{{ route('products.index') }}">Khám phá combo →</a>
        </div>
    </div>
</section>

<section class="benefits"><div class="container benefit-grid">
    <div><span>01</span><h3>Tươi mỗi sáng</h3><p>Chọn lọc và nhập mới hằng ngày.</p></div>
    <div><span>02</span><h3>Giao trong 2 giờ</h3><p>Nhanh chóng và bảo quản cẩn thận.</p></div>
    <div><span>03</span><h3>Đổi trả dễ dàng</h3><p>Hỗ trợ trong 24 giờ nếu chưa hài lòng.</p></div>
    <div><span>04</span><h3>Thanh toán an toàn</h3><p>COD tiện lợi, minh bạch từng khoản.</p></div>
</div></section>

<section class="section container">
    <div class="section-heading"><div><span class="eyebrow">Tìm theo nhu cầu</span><h2>Danh mục nổi bật</h2></div><a href="{{ route('products.index') }}">Xem tất cả →</a></div>
    <div class="category-grid">
        @foreach($categories as $category)
            <a class="category-card cat-{{ ($category->id % 4) + 1 }}" href="{{ route('products.index', ['category' => $category->slug]) }}"><span>@if($category->image_url)<img src="{{ $category->image_url }}" alt="{{ $category->name }}">@else{{ mb_substr($category->name, 0, 1) }}@endif</span><div><h3>{{ $category->name }}</h3><p>{{ $category->products_count ?? 'Khám phá ngay' }}</p></div></a>
        @endforeach
    </div>
</section>

<section id="featured" class="section container">
    <div class="section-heading"><div><span class="eyebrow">Được yêu thích</span><h2>Sản phẩm nổi bật</h2></div><a href="{{ route('products.index') }}">Mua thêm →</a></div>
    <div class="product-grid">@foreach($featured as $product)<x-product-card :product="$product" />@endforeach</div>
</section>

<section class="container promo"><div><span>ƯU ĐÃI THÀNH VIÊN MỚI</span><h2>Giảm 15% đơn hàng đầu tiên</h2><p>Nhập mã <b>WELCOME15</b> khi thanh toán. Áp dụng cho đơn từ 300.000đ.</p></div><a class="button light" href="{{ route('products.index') }}">Chọn sản phẩm</a></section>
<x-halloween-popup />
@endsection
