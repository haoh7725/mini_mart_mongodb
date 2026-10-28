@props(['order', 'showCreator' => false])
{{-- Dòng thời gian hành trình đơn hàng, dùng chung cho trang của khách và trang quản trị. --}}
<ol {{ $attributes->class('tracking-timeline') }}>
    @forelse($order->trackingEvents as $event)
        <li @class(['latest' => $loop->first])><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('H:i') }}<small>{{ $event->created_at->format('d/m/Y') }}</small></time><div><b>{{ $event->title }}</b>@if($event->description)<p>{{ $event->description }}</p>@endif @if($showCreator)<small>{{ $event->creator?->name ?? 'Hệ thống' }}</small>@endif</div></li>
    @empty
        <li class="latest"><time>{{ $order->created_at->format('H:i') }}<small>{{ $order->created_at->format('d/m/Y') }}</small></time><div><b>Đặt hàng thành công</b></div></li>
    @endforelse
</ol>
