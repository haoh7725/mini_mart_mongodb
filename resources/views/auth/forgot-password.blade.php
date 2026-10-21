@extends('layouts.app')
@section('title', 'Quên mật khẩu')
@section('content')
<section class="auth-page container"><form class="auth-card" method="post" action="{{ route('password.email') }}">@csrf
    <span class="eyebrow">Khôi phục tài khoản</span><h1>Quên mật khẩu</h1><p>Nhập email để nhận liên kết đặt lại mật khẩu.</p>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>@error('email')<small>{{ $message }}</small>@enderror</div>
    <button class="button primary full">Gửi hướng dẫn</button><div class="auth-links"><a href="{{ route('login') }}">Quay lại đăng nhập</a></div>
</form></section>
@endsection
