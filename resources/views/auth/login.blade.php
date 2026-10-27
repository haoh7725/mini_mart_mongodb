@extends('layouts.app')
@section('title', 'Đăng nhập')
@section('content')
<section class="auth-page container"><form class="auth-card" method="post" action="{{ route('login') }}">@csrf
    <span class="eyebrow">Tài khoản MiniMart</span><h1>Đăng nhập</h1><p>Quản lý đơn hàng và truy cập khu vực phù hợp với vai trò của bạn.</p>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>@error('email')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="password">Mật khẩu</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
    <label class="check"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập</label>
    <button class="button primary full">Đăng nhập</button><x-google-auth-button /><div class="auth-links"><a href="{{ route('password.request') }}">Quên mật khẩu?</a><a href="{{ route('register') }}">Tạo tài khoản</a></div>
</form></section>
@endsection
