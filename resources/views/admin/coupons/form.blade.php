@extends('admin.layouts.app')
@section('heading', 'Thiết lập mã giảm giá')
@section('content')
<form class="checkout-form" method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
@csrf @if($coupon->exists) @method('PUT') @endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<div class="field-grid">
<div class="field"><label for="code">Mã</label><input id="code" name="code" maxlength="50" required value="{{ old('code', $coupon->code) }}"></div>
<div class="field"><label for="type">Loại giảm</label><select id="type" name="type"><option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Phần trăm</option><option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Số tiền</option></select></div>
@foreach(['value'=>'Giá trị giảm', 'minimum_order_amount'=>'Đơn tối thiểu (đ)', 'maximum_discount_amount'=>'Giảm tối đa (đ, bỏ trống nếu không giới hạn)', 'usage_limit'=>'Giới hạn lượt dùng (bỏ trống nếu không giới hạn)'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" type="number" step="{{ $field === 'usage_limit' ? '1' : '0.01' }}" min="{{ $field === 'usage_limit' ? '1' : ($field === 'minimum_order_amount' ? '0' : '0.01') }}" name="{{ $field }}" value="{{ old($field, $coupon->$field ?? ($field === 'minimum_order_amount' ? 0 : '')) }}" @required(in_array($field, ['value','minimum_order_amount']))></div>
@endforeach
@foreach(['starts_at'=>'Bắt đầu', 'expires_at'=>'Hết hạn'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" type="datetime-local" name="{{ $field }}" value="{{ old($field, $coupon->$field?->format('Y-m-d\TH:i')) }}"></div>
@endforeach
<div class="field"><label for="is_active">Kích hoạt</label><select id="is_active" name="is_active"><option value="1" @selected(old('is_active', $coupon->is_active))>Có</option><option value="0" @selected(!old('is_active', $coupon->is_active))>Không</option></select></div>
</div><button class="button primary">Lưu</button> <a href="{{ route('admin.coupons.index') }}">Quay lại</a>
</form>
@endsection
