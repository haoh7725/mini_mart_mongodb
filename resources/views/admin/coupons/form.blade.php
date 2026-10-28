@extends('admin.layouts.app')
@section('title', $coupon->exists ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá')
@section('heading', 'Thiết lập mã giảm giá')
@section('content')
<div class="admin-page-head"><div><h1>{{ $coupon->exists ? 'Mã '.$coupon->code : 'Mã giảm giá mới' }}</h1><p>Các trường có dấu * là bắt buộc. Mã chỉ giảm trên tiền hàng, không giảm phí giao.</p></div></div>
<form class="admin-form" method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
@csrf @if($coupon->exists) @method('PUT') @endif
@php($type = old('type', $coupon->type ?? 'percentage'))
<div class="field wide"><label for="code">Mã{{ $coupon->exists ? ' *' : '' }}</label><div class="coupon-code-input"><input id="code" name="code" maxlength="50" @required($coupon->exists) value="{{ old('code', $coupon->code) }}" placeholder="{{ $coupon->exists ? '' : 'Bỏ trống để tự tạo, ví dụ: TET2027' }}"><button type="button" class="admin-btn" id="generate-code" title="Giữ tiền tố đang nhập, thêm phần ngẫu nhiên phía sau">Tạo mã ngẫu nhiên</button></div>@error('code')<small>{{ $message }}</small>@else<small class="field-hint">Tự gõ mã dễ nhớ (chữ, số, dấu - hoặc _) hoặc bấm "Tạo mã ngẫu nhiên". Nếu đang gõ sẵn tiền tố như SALE, mã sinh ra sẽ là SALE-XXXXXXXX.</small>@enderror</div>
<div class="field"><label for="type">Loại giảm *</label><select id="type" name="type"><option value="percentage" @selected($type === 'percentage')>Giảm theo phần trăm (%)</option><option value="fixed" @selected($type === 'fixed')>Giảm số tiền cố định (đ)</option></select>@error('type')<small>{{ $message }}</small>@enderror</div>
<div class="field"><label for="value">Giá trị giảm <span id="value-unit">{{ $type === 'fixed' ? '(đ)' : '(%)' }}</span> *</label><input id="value" type="number" step="0.01" min="0.01" @if($type !== 'fixed') max="100" @endif name="value" value="{{ old('value', $coupon->value) }}" placeholder="{{ $type === 'fixed' ? 'Ví dụ: 30000' : 'Ví dụ: 15' }}" required>@error('value')<small>{{ $message }}</small>@enderror</div>
<div class="field"><label for="minimum_order_amount">Đơn tối thiểu (đ) *</label><input id="minimum_order_amount" type="number" step="0.01" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', $coupon->minimum_order_amount ?? 0) }}" required>@error('minimum_order_amount')<small>{{ $message }}</small>@else<small class="field-hint">Nhập 0 nếu áp dụng cho mọi đơn.</small>@enderror</div>
<div class="field"><label for="maximum_discount_amount">Giảm tối đa (đ)</label><input id="maximum_discount_amount" type="number" step="0.01" min="0.01" name="maximum_discount_amount" value="{{ old('maximum_discount_amount', $coupon->maximum_discount_amount) }}" placeholder="Không giới hạn">@error('maximum_discount_amount')<small>{{ $message }}</small>@else<small class="field-hint">Nên đặt khi giảm theo phần trăm.</small>@enderror</div>
<div class="field"><label for="starts_at">Bắt đầu</label><input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}">@error('starts_at')<small>{{ $message }}</small>@else<small class="field-hint">Bỏ trống để áp dụng ngay.</small>@enderror</div>
<div class="field"><label for="expires_at">Hết hạn</label><input id="expires_at" type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}">@error('expires_at')<small>{{ $message }}</small>@else<small class="field-hint">Bỏ trống nếu không hết hạn.</small>@enderror</div>
<div class="field"><label for="usage_limit">Giới hạn lượt dùng</label><input id="usage_limit" type="number" step="1" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="Không giới hạn">@error('usage_limit')<small>{{ $message }}</small>@else<small class="field-hint">Tổng số đơn được dùng mã{{ $coupon->exists ? ', đã dùng '.$coupon->used_count.' lượt' : '' }}.</small>@enderror</div>
<div class="field"><label for="is_active">Trạng thái *</label><select id="is_active" name="is_active"><option value="1" @selected(old('is_active', $coupon->is_active))>Đang kích hoạt</option><option value="0" @selected(! old('is_active', $coupon->is_active))>Tạm khóa</option></select>@error('is_active')<small>{{ $message }}</small>@else<small class="field-hint">Mã bị khóa vẫn giữ lịch sử đơn đã dùng.</small>@enderror</div>
<div class="form-actions wide"><a class="admin-btn" href="{{ route('admin.coupons.index') }}">Hủy</a><button class="admin-btn primary">{{ $coupon->exists ? 'Lưu thay đổi' : 'Tạo mã giảm giá' }}</button></div>
</form>
<script>
document.getElementById('generate-code').addEventListener('click', () => {
    const input = document.getElementById('code');
    const alphabet = @json(\App\Models\Coupon::CODE_ALPHABET);
    const length = @json(\App\Models\Coupon::CODE_LENGTH);
    const values = crypto.getRandomValues(new Uint32Array(length));
    const random = Array.from(values, value => alphabet[value % alphabet.length]).join('');
    // Giữ phần tiền tố admin đã gõ (bỏ phần ngẫu nhiên cũ nếu bấm lại nhiều lần).
    const prefix = input.value.trim().toUpperCase().replace(new RegExp(`-[A-Z0-9]{${length}}$`), '').replace(/[^A-Z0-9_-]/g, '').replace(/[-_]+$/, '');
    input.value = prefix ? `${prefix}-${random}` : random;
    input.focus();
});
// Đơn vị của "Giá trị giảm" đổi theo loại giảm.
document.getElementById('type').addEventListener('change', event => {
    const fixed = event.target.value === 'fixed';
    const value = document.getElementById('value');
    document.getElementById('value-unit').textContent = fixed ? '(đ)' : '(%)';
    value.placeholder = fixed ? 'Ví dụ: 30000' : 'Ví dụ: 15';
    fixed ? value.removeAttribute('max') : value.setAttribute('max', '100');
});
</script>
@endsection
