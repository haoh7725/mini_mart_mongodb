@extends('layouts.app')
@section('title', 'Đơn hàng đã mua')
@section('content')
<section class="page-hero compact"><div class="container"><span class="eyebrow">Tài khoản</span><h1>Đơn hàng đã mua</h1></div></section>
<div class="container account-layout">
    <nav class="account-nav" aria-label="Tài khoản"><a href="{{ route('account.profile') }}">Hồ sơ</a><a class="active" href="{{ route('account.orders.index') }}">Đơn hàng đã mua</a></nav>
    <section class="account-panel">
        <nav class="order-tabs" aria-label="Lọc đơn hàng theo trạng thái">
            @foreach(\App\Models\Order::CUSTOMER_TABS as $key => $label)
                <a href="{{ route('account.orders.index', $key === 'all' ? [] : ['tab' => $key]) }}" @class(['active' => $tab === $key]) @if($tab === $key) aria-current="page" @endif>{{ $label }}@if(($counts[$key] ?? 0) > 0)<span>{{ $counts[$key] }}</span>@endif</a>
            @endforeach
        </nav>
        <div class="order-cards">
        @forelse($orders as $order)
            <article class="order-card">
                <header><div><b>{{ $order->code }}</b><small>Đặt lúc {{ $order->created_at->format('H:i d/m/Y') }}</small></div>
                    <span class="status status-{{ $order->status }}">{{ $order->displayStatusLabel() }}</span></header>
                @if($order->isInDelivery() && $order->estimated_delivery_at)
                    <p class="order-card-eta">🚚 {{ $order->status === 'shipping' ? 'Đang giao' : 'Chờ shipper lấy hàng' }} · Dự kiến giao trước <b>{{ $order->estimated_delivery_at->format('H:i') }}</b> {{ $order->estimated_delivery_at->isToday() ? 'hôm nay' : $order->estimated_delivery_at->format('d/m') }}</p>
                @endif
                <ul class="order-card-items">
                    @foreach($order->items->take(2) as $item)<li><span>{{ $item->product_name }}</span><small>× {{ number_format($item->quantity, 0, ',', '.') }}</small></li>@endforeach
                    @if($order->items_count > 2)<li class="more">và {{ $order->items_count - 2 }} sản phẩm khác</li>@endif
                </ul>
                <footer><span>Thành tiền: <b>{{ number_format($order->total_amount, 0, ',', '.') }}đ</b></span>
                    <span class="order-actions">@if($order->canBeCancelledByCustomer())<a class="danger" href="{{ route('account.orders.show', [$order->code, 'huy' => 1]) }}#huy-don">Hủy đơn</a>@endif<a href="{{ route('account.orders.show', $order->code) }}">{{ $order->isInDelivery() ? 'Theo dõi đơn' : 'Xem chi tiết' }}</a></span></footer>
            </article>
        @empty
            <p class="empty-cell">{{ $tab === 'all' ? 'Bạn chưa có đơn hàng nào.' : 'Chưa có đơn hàng nào ở mục này.' }} <a href="{{ route('products.index') }}">Mua sắm ngay</a></p>
        @endforelse
        </div>
        <div class="admin-pagination">{{ $orders->links() }}</div>
    </section>
</div>
@endsection
