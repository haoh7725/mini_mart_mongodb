@extends('layouts.app')
@section('title', 'Giao hàng')
@section('content')
<section class="page-hero compact"><div class="container"><span class="eyebrow">Shipper MiniMart</span><h1>Đơn cần giao</h1><p>Mỗi đơn giao trong {{ \App\Models\Order::DELIVERY_HOURS }} giờ kể từ khi shop xác nhận. Cập nhật hành trình để khách biết đơn đang ở đâu.</p></div></section>
<div class="container shipper-page">
    <nav class="order-tabs" aria-label="Lọc đơn giao">
        @foreach(\App\Http\Controllers\ShipperController::TABS as $key => $label)
            <a href="{{ route('shipper.index', ['tab' => $key]) }}" @class(['active' => $tab === $key]) @if($tab === $key) aria-current="page" @endif>{{ $label }}@if($counts[$key] > 0)<span>{{ $counts[$key] }}</span>@endif</a>
        @endforeach
    </nav>
    <div class="shipper-orders">
    @forelse($orders as $order)
        @php($cod = $codAmounts[$order->id] ?? null)
        <article class="shipper-order">
            <header><div><b>{{ $order->code }}</b><span class="status status-{{ $order->status }}">{{ $order->statusLabel() }}</span></div>
                @if($order->estimated_delivery_at && $order->status !== 'completed')<span @class(['eta', 'late' => $order->estimated_delivery_at->isPast()])>Giao trước {{ $order->estimated_delivery_at->format('H:i') }}</span>@endif</header>
            <dl>
                <dt>Người nhận</dt><dd>{{ $order->customer_name }} · <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></dd>
                <dt>Địa chỉ</dt><dd>{{ $order->shipping_address }} <a class="map-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->shipping_address) }}" target="_blank" rel="noopener">Mở bản đồ</a></dd>
                @if($order->customer_note)<dt>Ghi chú</dt><dd>{{ $order->customer_note }}</dd>@endif
                <dt>Hàng</dt><dd>@foreach($order->items as $item){{ $item->product_name }} × {{ number_format($item->quantity, 0, ',', '.') }}@if(! $loop->last), @endif @endforeach</dd>
                <dt>Thu hộ</dt><dd><b @class(['cod' => $cod])>{{ $cod ? 'Thu '.number_format($cod, 0, ',', '.').'đ tiền mặt' : 'Đã thanh toán online, không thu tiền' }}</b></dd>
            </dl>
            @if($order->latestTrackingEvent)<p class="last-event">Cập nhật gần nhất: {{ $order->latestTrackingEvent->title }} · {{ $order->latestTrackingEvent->created_at->format('H:i') }}</p>@endif
            @if(in_array($order->status, \App\Models\Order::AWAITING_PICKUP, true))
                <form method="post" action="{{ route('shipper.pickup', $order) }}">@csrf<button class="button primary full">Đã lấy hàng, bắt đầu giao</button></form>
            @elseif($order->status === 'shipping')
                <form method="post" action="{{ route('shipper.update', $order) }}" class="shipper-note">@csrf<label class="sr-only" for="note-{{ $order->id }}">Vị trí hoặc ghi chú</label><input id="note-{{ $order->id }}" name="note" maxlength="200" required placeholder="Vị trí hiện tại, ví dụ: Đang ở cầu Kênh Tẻ, còn 10 phút"><button class="button ghost">Gửi cập nhật</button></form>
                <div class="shipper-actions">
                    <form method="post" action="{{ route('shipper.deliver', $order) }}" data-confirm="Xác nhận đã giao đơn {{ $order->code }}{{ $cod ? ' và đã thu '.number_format($cod, 0, ',', '.').'đ' : '' }}?" data-confirm-title="Giao thành công?" data-confirm-ok="Đã giao">@csrf<button class="button primary full">Đã giao thành công</button></form>
                    <details class="shipper-fail"><summary>Giao chưa thành công</summary>
                        <form method="post" action="{{ route('shipper.fail', $order) }}">@csrf
                            @foreach(\App\Http\Controllers\ShipperController::FAIL_REASONS as $key => $reason)<label><input type="radio" name="reason" value="{{ $key }}" required> {{ $reason }}</label>@endforeach
                            <button class="button danger full">Ghi nhận</button>
                        </form>
                    </details>
                </div>
            @else
                <p class="tracking-banner done">Đã giao lúc {{ $order->delivered_at?->format('H:i') }}.</p>
            @endif
        </article>
    @empty
        <p class="empty-cell">Không có đơn nào ở mục này.</p>
    @endforelse
    </div>
</div>
@endsection
