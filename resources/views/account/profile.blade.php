@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân')
@section('content')
<section class="page-hero compact"><div class="container"><span class="eyebrow">Tài khoản</span><h1>Hồ sơ cá nhân</h1></div></section>
<div class="container account-layout">
    <nav class="account-nav" aria-label="Tài khoản"><a class="active" href="{{ route('account.profile') }}">Hồ sơ</a><a href="{{ route('account.orders.index') }}">Đơn hàng đã mua</a></nav>
    <div><section class="green-points-card"><div><span>Điểm Xanh</span><strong>{{ number_format($greenPoints,0,',','.') }}</strong><small>Mỗi sản phẩm SmartSave mang về 10 điểm khi đơn hoàn thành</small></div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21C7 18 4 14 4 9c0-3 2-5 5-5 2 0 3 1 3 3 0-2 1-3 3-3 3 0 5 2 5 5 0 5-3 9-8 12Z"/></svg></section>@if($pointHistory->isNotEmpty())<div class="point-history">@foreach($pointHistory as $entry)<span>{{ $entry->reason }} <b>+{{ $entry->points }}</b></span>@endforeach</div>@endif
    <form class="account-panel account-form" method="post" action="{{ route('account.profile.update') }}">@csrf @method('patch')
        <div><h2>Thông tin tài khoản</h2><p>Cập nhật thông tin dùng khi đặt hàng.</p></div>
        <div class="field-grid">
            <div class="field"><label for="name">Họ và tên *</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required>@error('name')<small>{{ $message }}</small>@enderror</div>
            <div class="field"><label for="phone">Số điện thoại</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}">@error('phone')<small>{{ $message }}</small>@enderror</div>
            <div class="field wide"><label for="email">Email *</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<small>{{ $message }}</small>@enderror</div>
        </div>
        <hr id="doi-mat-khau"><div><h2>Đổi mật khẩu</h2><p>Để trống nếu bạn không muốn đổi mật khẩu.</p></div>
        <div class="field-grid">
            <div class="field wide"><label for="current_password">Mật khẩu hiện tại</label><input id="current_password" type="password" name="current_password">@error('current_password')<small>{{ $message }}</small>@enderror</div>
            <div class="field"><label for="password">Mật khẩu mới</label><input id="password" type="password" name="password">@error('password')<small>{{ $message }}</small>@enderror</div>
            <div class="field"><label for="password_confirmation">Nhập lại mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation"></div>
        </div>
        <button class="button primary">Lưu thay đổi</button>
    </form></div>
</div>
@endsection
