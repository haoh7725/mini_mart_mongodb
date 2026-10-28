@extends('admin.layouts.app')
@section('heading', 'Mã giảm giá')
@section('content')
<a class="button primary" href="{{ route('admin.coupons.create') }}">Thêm mã giảm giá</a>
<div class="table-wrap"><table><thead><tr><th>Mã</th><th>Mức giảm</th><th>Lượt dùng</th><th>Hết hạn</th><th>Kích hoạt</th><th></th></tr></thead><tbody>
@forelse($coupons as $coupon)
<tr><td>{{ $coupon->code }}</td><td>{{ number_format($coupon->value) }}{{ $coupon->type === 'percentage' ? '%' : 'đ' }}</td><td>{{ $coupon->used_count }} / {{ $coupon->usage_limit ?? 'Không giới hạn' }}</td><td>{{ $coupon->expires_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}</td><td>{{ $coupon->is_active ? 'Có' : 'Không' }}</td><td><a href="{{ route('admin.coupons.edit', $coupon) }}">Sửa / Khóa</a></td></tr>
@empty<tr><td colspan="6">Chưa có mã giảm giá.</td></tr>@endforelse
</tbody></table></div>{{ $coupons->links() }}
@endsection
