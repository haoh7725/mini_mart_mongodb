@props(['product'])
<article class="product-card">
    <a class="product-visual tone-{{ ($product->id % 5) + 1 }}" href="{{ route('products.show', $product) }}" aria-label="Xem {{ $product->name }}">
        @if($product->primary_image)<img src="{{ asset('storage/'.$product->primary_image) }}" alt="{{ $product->name }}" loading="lazy">@else<span>{{ mb_substr($product->category?->name ?? 'Sản phẩm', 0, 1) }}</span>@endif
        @if($product->sale_price)<b class="sale-badge">-{{ round((1 - $product->sale_price / $product->selling_price) * 100) }}%</b>@endif
    </a>
    <div class="product-body">
        <div class="product-meta">{{ $product->category?->name }} · {{ $product->sku }}</div>
        <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
        <div class="rating" aria-label="Đánh giá 4.8 trên 5">★★★★★ <span>4.8</span></div>
        <div class="product-bottom">
            <div class="price"><strong>{{ number_format($product->current_price, 0, ',', '.') }}đ</strong>@if($product->sale_price)<del>{{ number_format($product->selling_price, 0, ',', '.') }}đ</del>@endif</div>
            <form action="{{ route('cart.store', $product) }}" method="post">@csrf<button class="add-button" aria-label="Thêm {{ $product->name }} vào giỏ" @disabled($product->stock <= 0)>+</button></form>
        </div>
    </div>
</article>
