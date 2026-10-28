@php
    $isStaff = auth()->user()->isStaff();
    ['unread' => $unread, 'items' => $items] = \App\Support\Notifier::feed(auth()->user(), withPendingOrders: false);
@endphp
<details class="notify-bell" data-dropdown data-notify data-feed="{{ route('notifications.index') }}" data-staff="{{ $isStaff ? 1 : 0 }}" data-seen='@json($items->pluck('id'))'>
    <summary aria-label="Thông báo{{ $unread ? ', '.$unread.' chưa đọc' : '' }}">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <b class="notify-count" data-notify-count @if(! $unread) hidden @endif>{{ $unread > 99 ? '99+' : $unread }}</b>
    </summary>
    <div class="notify-panel">
        <header><b>Thông báo</b><form method="post" action="{{ route('notifications.read-all') }}">@csrf<button>Đánh dấu đã đọc hết</button></form></header>
        @if($isStaff)<button type="button" class="notify-enable" data-notify-enable hidden>🔔 Bật thông báo trên máy tính khi có đơn mới</button>@endif
        <ul data-notify-list>
            @forelse($items as $item)
                <li @class(['unread' => ! $item['read']])><a href="{{ $item['url'] }}"><span class="notify-icon notify-{{ $item['kind'] }}" aria-hidden="true">{{ $item['icon'] }}</span><span><b>{{ $item['title'] }}</b>@if($item['body'])<small>{{ $item['body'] }}</small>@endif<time>{{ $item['time'] }}</time></span></a></li>
            @empty
                <li class="notify-empty">Chưa có thông báo nào.</li>
            @endforelse
        </ul>
    </div>
</details>
