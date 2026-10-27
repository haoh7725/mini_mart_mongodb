@extends('layouts.app')
@section('title', 'Đăng ký')
@section('content')
<section class="auth-page container"><form class="auth-card" method="post" action="{{ route('register') }}">@csrf
    <span class="eyebrow">Thành viên mới</span><h1>Tạo tài khoản</h1>
    <div class="field"><label for="name">Họ và tên</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="phone">Số điện thoại</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="password">Mật khẩu</label><input id="password" type="password" name="password" autocomplete="new-password" required>@error('password')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="password_confirmation">Nhập lại mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
    <button class="button primary full">Đăng ký</button><x-google-auth-button label="Đăng ký bằng Google" /><div class="auth-links"><a href="{{ route('login') }}">Đã có tài khoản? Đăng nhập</a></div>
</form></section>
@endsection
