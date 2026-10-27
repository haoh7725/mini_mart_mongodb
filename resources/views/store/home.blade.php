@extends('layouts.app')
@section('title', 'Trang chủ')
@section('content')
@if($heroSlides->isNotEmpty())
<section id="hero-slider" class="hero-slider carousel slide" data-bs-ride="carousel" data-bs-interval="5500" aria-roledescription="carousel" aria-label="Ưu đãi nổi bật">
    <div class="carousel-inner">
        @foreach($heroSlides as $slide)
            @php($product = $slide['product'])
            <div class="carousel-item hero-slide theme-{{ $slide['theme'] }} {{ $loop->first ? 'active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">
                <div class="container hero-slide-grid">
                    <div class="hero-copy">
                        <div class="eyebrow">{{ $slide['eyebrow'] }}</div>
                        <{{ $loop->first ? 'h1' : 'h2' }} class="hero-title">{{ $slide['title'] }}<br><em>{{ $slide['highlight'] }}</em></{{ $loop->first ? 'h1' : 'h2' }}>
                        <p>{{ $slide['text'] }}</p>
                        <div class="hero-actions">
                            <form action="{{ route('cart.store', $product) }}" method="post">@csrf
                                <input type="hidden" name="quantity" value="1"><input type="hidden" name="buy_now" value="1">
                                <button class="button primary hero-buy">Đặt hàng ngay <span aria-hidden="true">→</span></button>
                            </form>
                            <a class="button ghost" href="{{ route('products.index', ['category' => $slide['category']]) }}">{{ $slide['link'] }}</a>
                        </div>
                        <div class="trust-row"><span><b>2 giờ</b> giao nhanh</span><span><b>300K</b> miễn phí giao</span><span><b>4.9/5</b> hài lòng</span></div>
                    </div>
                    <div class="hero-visual">
                        <x-hero-art :theme="$slide['theme']" />
                        <a class="hero-photo" href="{{ route('products.show', $product) }}" tabindex="-1">
                            @if($product->primary_image_url)<img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" @if(! $loop->first) loading="lazy" @endif>@else<span>{{ mb_substr($product->name, 0, 1) }}</span>@endif
                        </a>
                        @if($slide['discount'] > 0)<span class="hero-discount">-{{ $slide['discount'] }}%</span>@endif
                        <div class="hero-price-tag">
                            <small>{{ $product->name }}</small>
                            <div><strong>{{ number_format($product->current_price, 0, ',', '.') }}đ</strong>@if($product->current_price < $product->selling_price)<del>{{ number_format($product->selling_price, 0, ',', '.') }}đ</del>@endif</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @if($heroSlides->count() > 1)
        <div class="container hero-nav">
            <div class="carousel-indicators hero-dots">
                @foreach($heroSlides as $slide)
                    <button type="button" data-bs-target="#hero-slider" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" @if($loop->first) aria-current="true" @endif aria-label="Banner {{ $loop->iteration }}: {{ $slide['eyebrow'] }}"></button>
                @endforeach
            </div>
            <div class="hero-arrows">
                <button type="button" data-bs-target="#hero-slider" data-bs-slide="prev" aria-label="Banner trước"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
                <button type="button" data-bs-target="#hero-slider" data-bs-slide="next" aria-label="Banner tiếp theo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
            </div>
        </div>
    @endif
</section>
@endif

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
