@extends('layouts.app')
@section('title', 'Thanh toán')
@section('content')
<section class="page-hero compact"><div class="container"><span class="eyebrow">Bước 2 / 2</span><h1>Thanh toán</h1></div></section>
<form class="container checkout-layout" action="{{ route('checkout.store') }}" method="post" id="checkout-form">@csrf
    <section class="checkout-form">
        <h2>Thông tin giao hàng</h2>
        <div class="field-grid">
            <div class="field"><label for="customer_name">Họ và tên *</label><input id="customer_name" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" autocomplete="name" required>@error('customer_name')<small>{{ $message }}</small>@enderror</div>
            <div class="field"><label for="customer_phone">Số điện thoại *</label><input id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}" autocomplete="tel" required>@error('customer_phone')<small>{{ $message }}</small>@enderror</div>
            <div class="field wide"><label for="customer_email">Email</label><input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email', $customer?->email) }}" autocomplete="email"></div>
            <div class="field"><label for="province">Tỉnh/Thành phố *</label><select id="province" required><option value="">Đang tải danh sách...</option></select></div>
            <div class="field"><label for="ward">Phường/Xã *</label><select id="ward" required disabled><option value="">Chọn tỉnh/thành trước</option></select></div>
            <div class="field wide"><label for="address_detail">Số nhà, tên đường *</label><textarea id="address_detail" name="address_detail" rows="2" required>{{ old('address_detail') }}</textarea></div>
            <input type="hidden" id="province_name" name="province_name" value="{{ old('province_name') }}">
            <input type="hidden" id="ward_name" name="ward_name" value="{{ old('ward_name') }}">
            <input type="hidden" id="shipping_address" name="shipping_address" value="{{ old('shipping_address') }}">
            @error('shipping_address')<div class="field wide"><small>{{ $message }}</small></div>@enderror
            <div class="field wide"><label for="customer_note">Ghi chú cho cửa hàng</label><textarea id="customer_note" name="customer_note" rows="2">{{ old('customer_note') }}</textarea></div>
        </div>
        <h2>Phương thức thanh toán</h2>
        <label class="payment-option"><input type="radio" {{ old('payment', 'cod') === 'cod' ? 'checked' : '' }} name="payment" value="cod"><span><b>Thanh toán khi nhận hàng (COD)</b><small>Kiểm tra đơn hàng và thanh toán cho nhân viên giao hàng.</small></span></label>
        <label class="payment-option"><input type="radio" {{ old('payment') === 'vnpay' ? 'checked' : '' }} name="payment" value="vnpay"><span><b>Thanh toán trực tuyến qua VNPay</b><small>Thẻ ATM, tài khoản ngân hàng, QR hoặc thẻ quốc tế trong môi trường sandbox.</small></span></label>
        @error('payment')<small class="text-danger">{{ $message }}</small>@enderror
    </section>
    <aside class="order-summary"><h2>Đơn hàng ({{ $items->sum('quantity') }})</h2>@foreach($items as $item)<div class="checkout-item"><span>{{ $item['product']->name }} <small>× {{ $item['quantity'] }}</small></span><strong>{{ number_format($item['line_total'], 0, ',', '.') }}đ</strong></div>@endforeach<hr><div><span>Tạm tính</span><strong>{{ number_format($subtotal, 0, ',', '.') }}đ</strong></div><div><span>Phí giao</span><strong>{{ $subtotal >= 300000 ? 'Miễn phí' : '25.000đ' }}</strong></div><div class="summary-total"><span>Tổng thanh toán</span><strong>{{ number_format($subtotal + ($subtotal >= 300000 ? 0 : 25000), 0, ',', '.') }}đ</strong></div><button class="button primary full">Đặt hàng</button><p class="policy">Bằng việc đặt hàng, bạn đồng ý với điều khoản mua hàng của MiniMart.</p></aside>
</form>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const province = document.getElementById('province');
    const ward = document.getElementById('ward');
    const provinceName = document.getElementById('province_name');
    const wardName = document.getElementById('ward_name');
    const shippingAddress = document.getElementById('shipping_address');
    const oldProvince = @json(old('province_name'));
    const oldWard = @json(old('ward_name'));
    const fill = (select, items, placeholder) => {
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach(item => select.add(new Option(item.name, item.code)));
    };
    async function loadWards(code) {
        ward.disabled = true;
        fill(ward, [], 'Đang tải phường/xã...');
        const url = @json(route('address.wards', ['provinceCode' => '__CODE__'])).replace('__CODE__', code);
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error();
            fill(ward, await response.json(), 'Chọn phường/xã');
            ward.disabled = false;
            if (oldWard) {
                const option = [...ward.options].find(item => item.text === oldWard);
                if (option) ward.value = option.value;
            }
        } catch {
            fill(ward, [], 'Không tải được phường/xã');
        }
    }
    try {
        const response = await fetch(@json(route('address.provinces')));
        if (!response.ok) throw new Error();
        fill(province, await response.json(), 'Chọn tỉnh/thành phố');
        if (oldProvince) {
            const option = [...province.options].find(item => item.text === oldProvince);
            if (option) { province.value = option.value; await loadWards(option.value); }
        }
    } catch {
        fill(province, [], 'Không tải được danh sách, vui lòng thử lại');
    }
    province.addEventListener('change', async () => {
        provinceName.value = province.options[province.selectedIndex]?.text || '';
        wardName.value = '';
        if (province.value) await loadWards(province.value);
        else { ward.disabled = true; fill(ward, [], 'Chọn tỉnh/thành trước'); }
    });
    ward.addEventListener('change', () => wardName.value = ward.options[ward.selectedIndex]?.text || '');
    document.getElementById('checkout-form').addEventListener('submit', () => {
        shippingAddress.value = [document.getElementById('address_detail').value, wardName.value, provinceName.value].filter(Boolean).join(', ');
    });
});
</script>
@endsection
