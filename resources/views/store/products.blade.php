@extends('layouts.app')
@section('title', 'Cửa hàng')
@section('content')
@php
    $customPrice = $filters['price'] === '' && ($filters['min'] !== null || $filters['max'] !== null);
    $chips = collect([
        $filters['q'] !== '' ? ['label' => 'Từ khóa: “'.$filters['q'].'”', 'remove' => ['q']] : null,
        $filters['category'] ? ['label' => $categories->firstWhere('slug', $filters['category'])?->name ?? $filters['category'], 'remove' => ['category']] : null,
        $filters['brand'] ? ['label' => $brands->firstWhere('slug', $filters['brand'])?->name ?? $filters['brand'], 'remove' => ['brand']] : null,
        $filters['price'] ? ['label' => $priceRanges[$filters['price']]['label'], 'remove' => ['price']] : null,
        $customPrice ? ['label' => ($filters['min'] !== null ? number_format($filters['min'], 0, ',', '.').'đ' : '0đ').' – '.($filters['max'] !== null ? number_format($filters['max'], 0, ',', '.').'đ' : '…'), 'remove' => ['min_price', 'max_price']] : null,
        $filters['sale'] ? ['label' => 'Đang giảm giá', 'remove' => ['sale']] : null,
    ])->filter();
    $without = fn (array $keys) => route('products.index', collect(request()->query())->except([...$keys, 'page'])->all());
@endphp
<section class="page-hero"><div class="container"><span class="eyebrow">MiniMart online</span><h1>Cửa hàng</h1><p>Chọn thực phẩm và nhu yếu phẩm chất lượng cho gia đình bạn.</p></div></section>
<div class="container shop-layout">
    <details class="shop-filter" id="shop-filter" open>
        <summary><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7.5V19l-4 1v-7.5z"/></svg>Bộ lọc tìm kiếm</span>@if($chips->isNotEmpty())<b>{{ $chips->count() }}</b>@endif</summary>
        <form class="filter-form" action="{{ route('products.index') }}" method="get">
            @if($filters['q'] !== '')<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            @if($filters['sort'])<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif

            <fieldset class="filter-group">
                <legend>Danh mục</legend>
                <label class="filter-option"><input type="radio" name="category" value="" @checked(! $filters['category']) data-autosubmit><span>Tất cả danh mục</span></label>
                @foreach($categories as $category)
                    <label class="filter-option"><input type="radio" name="category" value="{{ $category->slug }}" @checked($filters['category'] === $category->slug) data-autosubmit><span>{{ $category->name }}</span><small>{{ $category->filter_count }}</small></label>
                @endforeach
            </fieldset>

            @if($brands->isNotEmpty())
            <fieldset class="filter-group">
                <legend>Thương hiệu</legend>
                <label class="filter-option"><input type="radio" name="brand" value="" @checked(! $filters['brand']) data-autosubmit><span>Tất cả thương hiệu</span></label>
                @foreach($brands as $brand)
                    <label class="filter-option {{ $brand->filter_count ? '' : 'is-empty' }}"><input type="radio" name="brand" value="{{ $brand->slug }}" @checked($filters['brand'] === $brand->slug) data-autosubmit><span>{{ $brand->name }}</span><small>{{ $brand->filter_count }}</small></label>
                @endforeach
            </fieldset>
            @endif

            <fieldset class="filter-group">
                <legend>Khoảng giá (đ)</legend>
                <div class="price-chips">
                    @foreach($priceRanges as $key => $range)
                        <label class="price-chip"><input type="radio" name="price" value="{{ $key }}" @checked($filters['price'] === $key) data-autosubmit data-clear-custom><span>{{ $range['label'] }}</span></label>
                    @endforeach
                </div>
                <div class="price-inputs">
                    <label class="sr-only" for="min_price">Giá từ</label><input id="min_price" name="min_price" type="number" min="0" step="1000" inputmode="numeric" placeholder="Từ" value="{{ $customPrice ? $filters['min'] : '' }}">
                    <span aria-hidden="true">–</span>
                    <label class="sr-only" for="max_price">Giá đến</label><input id="max_price" name="max_price" type="number" min="0" step="1000" inputmode="numeric" placeholder="Đến" value="{{ $customPrice ? $filters['max'] : '' }}">
                </div>
            </fieldset>

            <fieldset class="filter-group">
                <legend>Ưu đãi</legend>
                <label class="filter-option"><input type="checkbox" name="sale" value="1" @checked($filters['sale']) data-autosubmit><span>Chỉ sản phẩm đang giảm giá</span></label>
            </fieldset>

            <div class="filter-actions"><button class="button primary full">Áp dụng</button>@if($chips->isNotEmpty())<a href="{{ route('products.index') }}">Xóa tất cả bộ lọc</a>@endif</div>
        </form>
    </details>
    <section class="shop-content">
        <div class="shop-toolbar"><p>Tìm thấy <b>{{ $products->total() }}</b> sản phẩm</p><form>@foreach(collect(request()->query())->except(['sort', 'page']) as $key => $value)@if(is_string($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach<label for="sort">Sắp xếp</label><select id="sort" name="sort" onchange="this.form.submit()"><option value="">Mới nhất</option><option value="price_asc" @selected($filters['sort']==='price_asc')>Giá thấp đến cao</option><option value="price_desc" @selected($filters['sort']==='price_desc')>Giá cao đến thấp</option></select></form></div>
        @if($chips->isNotEmpty())
            <div class="filter-chips" aria-label="Bộ lọc đang áp dụng">
                @foreach($chips as $chip)<a href="{{ $without($chip['remove']) }}" aria-label="Bỏ lọc {{ $chip['label'] }}">{{ $chip['label'] }} <span aria-hidden="true">×</span></a>@endforeach
                <a class="clear" href="{{ route('products.index') }}">Xóa tất cả</a>
            </div>
        @endif
        @if($products->isEmpty())<div class="empty-state"><h2>Chưa tìm thấy sản phẩm</h2><p>Hãy thử bỏ bớt bộ lọc hoặc chọn khoảng giá khác.</p><a class="button primary" href="{{ route('products.index') }}">Xem tất cả</a></div>@else<div class="product-grid">@foreach($products as $product)<x-product-card :product="$product" />@endforeach</div>{{ $products->links() }}@endif
    </section>
</div>
<script>
    (() => {
        const panel = document.getElementById('shop-filter');
        const form = panel.querySelector('form');
        if (window.matchMedia('(max-width: 900px)').matches) panel.removeAttribute('open');
        form.querySelectorAll('[data-autosubmit]').forEach(input => input.addEventListener('change', () => {
            if (input.hasAttribute('data-clear-custom')) form.querySelectorAll('.price-inputs input').forEach(el => el.value = '');
            form.requestSubmit();
        }));
        form.querySelectorAll('.price-inputs input').forEach(input => input.addEventListener('input', () => {
            form.querySelectorAll('[name="price"]').forEach(radio => radio.checked = false);
        }));
        // Không gửi ô trống để URL gọn.
        form.addEventListener('formdata', event => {
            for (const [key, value] of [...event.formData.entries()]) if (value === '') event.formData.delete(key);
        });
    })();
</script>
@endsection
