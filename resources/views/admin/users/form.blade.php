@extends('admin.layouts.app')
@section('title',$user->exists?'Sửa tài khoản':'Thêm tài khoản')
@section('heading','Super Admin · Tài khoản & phân quyền')
@section('content')
<div class="admin-page-head"><div><h1>{{ $user->exists ? 'Chỉnh sửa tài khoản' : 'Thêm tài khoản' }}</h1><p>{{ $user->exists ? 'Cập nhật thông tin, vai trò hoặc khóa quyền truy cập.' : 'Tạo tài khoản quản trị hoặc khách hàng mới.' }}</p></div></div>
<form class="admin-form" method="post" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">@csrf @if($user->exists)@method('put')@endif
    <div class="field"><label for="name">Họ và tên *</label><input id="name" name="name" value="{{ old('name',$user->name) }}" required>@error('name')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="email">Email *</label><input id="email" type="email" name="email" value="{{ old('email',$user->email) }}" required>@error('email')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="phone">Số điện thoại</label><input id="phone" name="phone" value="{{ old('phone',$user->phone) }}">@error('phone')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="role_id">Vai trò *</label><select id="role_id" name="role_id" required>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string)old('role_id',$user->role_id)===(string)$role->id)>{{ $role->name }} — {{ $role->description }}</option>@endforeach</select>@error('role_id')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="is_active">Trạng thái *</label><select id="is_active" name="is_active"><option value="1" @selected((string)old('is_active',$user->exists ? (int)$user->is_active : 1)==='1')>Đang hoạt động</option><option value="0" @selected((string)old('is_active',$user->exists ? (int)$user->is_active : 1)==='0')>Khóa tài khoản</option></select>@error('is_active')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="password">{{ $user->exists ? 'Mật khẩu mới' : 'Mật khẩu *' }}</label><input id="password" type="password" name="password" {{ $user->exists ? '' : 'required' }}><small>{{ $user->exists ? 'Để trống nếu không đổi mật khẩu.' : 'Tối thiểu 8 ký tự.' }}</small>@error('password')<small>{{ $message }}</small>@enderror</div>
    <div class="field"><label for="password_confirmation">Nhập lại mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }}></div>
    <div class="form-actions wide"><a class="admin-btn" href="{{ route('admin.users.index') }}">Hủy</a><button class="admin-btn primary">{{ $user->exists ? 'Lưu thay đổi' : 'Tạo tài khoản' }}</button></div>
</form>
@endsection
