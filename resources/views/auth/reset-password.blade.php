@extends('layouts.app')
@section('title', 'Đặt lại mật khẩu')
@section('content')
<section class="auth-page container"><form class="auth-card" method="post" action="{{ route('password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}"><span class="eyebrow">Bảo mật tài khoản</span><h1>Đặt lại mật khẩu</h1>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email',$email) }}" required></div>
    <div class="field"><label for="password">Mật khẩu mới</label><input id="password" type="password" name="password" autocomplete="new-password" required>@error('password')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="password_confirmation">Nhập lại mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
    <button class="button primary full">Cập nhật mật khẩu</button>
</form></section>
@endsection
