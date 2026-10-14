@extends('layouts.app')
@section('title', 'Cửa hàng')
@section('content')
<section class="page-hero"><div class="container"><span class="eyebrow">MiniMart online</span><h1>Cửa hàng</h1><p>Chọn thực phẩm và nhu yếu phẩm chất lượng cho gia đình bạn.</p></div></section>
<div class="container shop-layout">
    <aside class="filters"><h2>Danh mục</h2><a class="{{ request('category') ? '' : 'active' }}" href="{{ route('products.index') }}">Tất cả sản phẩm</a>@foreach($categories as $category)<a class="{{ request('category') === $category->slug ? 'active' : '' }}" href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }}</a>@endforeach</aside>
    <section class="shop-content">
        <div class="shop-toolbar"><p>Tìm thấy <b>{{ $products->total() }}</b> sản phẩm</p><form><input type="hidden" name="q" value="{{ request('q') }}"><input type="hidden" name="category" value="{{ request('category') }}"><label for="sort">Sắp xếp</label><select id="sort" name="sort" onchange="this.form.submit()"><option value="">Mới nhất</option><option value="price_asc" @selected(request('sort')==='price_asc')>Giá thấp đến cao</option><option value="price_desc" @selected(request('sort')==='price_desc')>Giá cao đến thấp</option></select></form></div>
        @if($products->isEmpty())<div class="empty-state"><h2>Chưa tìm thấy sản phẩm</h2><p>Hãy thử từ khóa hoặc danh mục khác.</p><a class="button primary" href="{{ route('products.index') }}">Xem tất cả</a></div>@else<div class="product-grid">@foreach($products as $product)<x-product-card :product="$product" />@endforeach</div>{{ $products->links() }}@endif
    </section>
</div>
@endsection
