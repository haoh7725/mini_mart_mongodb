@extends('layouts.app')
@section('title','Giải cứu thực phẩm')
@section('content')
<section class="rescue-hero"><div class="container rescue-hero-grid"><div><span class="eyebrow">MiniMart SmartSave</span><h1>Mua tốt hơn,<br>giảm lãng phí hơn.</h1><p>Các sản phẩm vẫn đảm bảo chất lượng nhưng gần hạn sử dụng được ưu đãi để đến đúng căn bếp thay vì bị bỏ đi.</p><div class="rescue-impact"><span><b>{{ number_format($impact['rescued'],0,',','.') }}</b>Sản phẩm đã cứu</span><span><b>{{ number_format($impact['saved'],0,',','.') }}đ</b>Khách hàng tiết kiệm</span></div></div><div class="rescue-mark">♻<small>MỖI MÓN ĐƯỢC MUA<br>LÀ MỘT PHẦN LÃNG PHÍ ĐƯỢC GIẢM</small></div></div></section>
<section class="section container"><div class="section-heading"><div><span class="eyebrow">Ưu đãi có trách nhiệm</span><h2>Sản phẩm đang được giải cứu</h2></div></div>
@if($campaigns->isEmpty())<div class="empty-state"><div class="empty-icon">✓</div><h2>Hiện chưa có sản phẩm cần giải cứu</h2><p>Kho hàng đang ở trạng thái tốt. Hãy quay lại sau nhé.</p><a class="button primary" href="{{ route('products.index') }}">Tiếp tục mua sắm</a></div>@else<div class="product-grid">@foreach($campaigns as $campaign)<div class="rescue-product"><div class="rescue-expiry">Còn {{ max(0,today()->diffInDays($campaign->inventoryLot->expires_at,false)) }} ngày · Giảm {{ $campaign->discount_percent }}%</div><x-product-card :product="$campaign->product" /></div>@endforeach</div>@endif
</section>
@endsection
