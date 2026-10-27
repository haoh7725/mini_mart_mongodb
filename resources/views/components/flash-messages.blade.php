@php
    $flashes = collect([
        session('success') ? ['type' => 'success', 'title' => 'Thành công', 'messages' => [session('success')]] : null,
        session('info') ? ['type' => 'info', 'title' => 'Thông báo', 'messages' => [session('info')]] : null,
        session('warning') ? ['type' => 'warning', 'title' => 'Lưu ý', 'messages' => [session('warning')]] : null,
        $errors->any() ? ['type' => 'error', 'title' => $errors->count() > 1 ? 'Vui lòng kiểm tra lại' : 'Có lỗi xảy ra', 'messages' => array_slice(array_unique($errors->all()), 0, 4)] : null,
    ])->filter();
    $icons = [
        'success' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'info' => '<path d="M12 11v6M12 7.5h.01"/>',
        'warning' => '<path d="M12 8v5M12 16.5h.01"/>',
        'error' => '<path d="m7 7 10 10M17 7 7 17"/>',
    ];
@endphp
<div class="flash-stack" id="flash-stack" aria-live="polite">
    @foreach($flashes as $flash)
        <div class="flash flash-{{ $flash['type'] }}" role="{{ $flash['type'] === 'error' ? 'alert' : 'status' }}" data-flash data-timeout="{{ $flash['type'] === 'error' ? 8000 : 5000 }}">
            <span class="flash-icon" aria-hidden="true"><svg viewBox="0 0 24 24">{!! $icons[$flash['type']] !!}</svg></span>
            <div class="flash-body">
                <b>{{ $flash['title'] }}</b>
                @if(count($flash['messages']) > 1)
                    <ul>@foreach($flash['messages'] as $message)<li>{{ $message }}</li>@endforeach</ul>
                @else
                    <p>{{ $flash['messages'][0] }}</p>
                @endif
            </div>
            <button type="button" class="flash-close" data-flash-close aria-label="Đóng thông báo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17"/></svg></button>
            <span class="flash-progress" aria-hidden="true"></span>
        </div>
    @endforeach
</div>
<template id="flash-icons">@foreach($icons as $type => $path)<svg data-type="{{ $type }}" viewBox="0 0 24 24">{!! $path !!}</svg>@endforeach</template>
