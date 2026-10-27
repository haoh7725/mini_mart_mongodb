@extends('layouts.app')
@section('title', 'Đơn hàng '.$order->code)
@section('content')
@php($statuses = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','processing'=>'Đang xử lý','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'])
<section class="page-hero compact"><div class="container"><a class="eyebrow" href="{{ route('account.orders.index') }}">← Đơn hàng đã mua</a><h1>{{ $order->code }}</h1></div></section>
<div class="container account-order-grid">
    <section class="account-panel">
        <div class="panel-head"><h2>Sản phẩm</h2><span class="status status-{{ $order->status }}">{{ $statuses[$order->status] ?? $order->status }}</span></div>
        <div class="table-wrap"><table><thead><tr><th>Tên sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>
        @foreach($order->items as $item)
            <tr><td><b>{{ $item->product_name }}</b><small class="block">{{ $item->sku }}</small></td><td>{{ number_format($item->quantity, 0, ',', '.') }}</td><td>{{ number_format($item->unit_price, 0, ',', '.') }}đ</td><td>{{ number_format($item->line_total, 0, ',', '.') }}đ</td></tr>
            @if($order->status === 'completed' && $item->product_id)
                <tr class="review-row"><td colspan="4">
                @if($item->review)
                    <div class="review-sent"><b>Đánh giá của bạn: {{ str_repeat('★', $item->review->rating) }}</b><span class="status">{{ ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Đã ẩn'][$item->review->status] }}</span><p>{{ $item->review->content }}</p></div>
                @else
                    <details class="review-form"><summary>Viết đánh giá đã xác minh</summary>
                        <form method="post" action="{{ route('account.reviews.store', $item) }}">@csrf
                            <div class="rating-input"><label for="rating-{{ $item->id }}">Số sao</label><select id="rating-{{ $item->id }}" name="rating" required>@foreach([5,4,3,2,1] as $rating)<option value="{{ $rating }}">{{ $rating }} sao</option>@endforeach</select></div>
                            <label for="content-{{ $item->id }}">Nhận xét</label><textarea id="content-{{ $item->id }}" name="content" minlength="10" maxlength="1000" required></textarea>
                            <button class="button primary">Gửi đánh giá</button>
                        </form>
                    </details>
                @endif
                </td></tr>
            @endif
        @endforeach
        </tbody></table></div>
    </section>
    <aside class="account-panel order-detail">
        <h2>Thông tin đơn hàng</h2><dl><dt>Người nhận</dt><dd>{{ $order->customer_name }} · {{ $order->customer_phone }}</dd><dt>Địa chỉ</dt><dd>{{ $order->shipping_address }}</dd><dt>Phương thức</dt><dd>{{ ($payment->method ?? 'cod') === 'online' ? 'VNPay' : 'COD' }}</dd><dt>Thanh toán</dt><dd>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</dd>@if($order->customer_note)<dt>Ghi chú</dt><dd>{{ $order->customer_note }}</dd>@endif</dl>
        <div class="price-row"><span>Tạm tính</span><b>{{ number_format($order->subtotal,0,',','.') }}đ</b></div><div class="price-row"><span>Phí giao hàng</span><b>{{ number_format($order->shipping_fee,0,',','.') }}đ</b></div><div class="price-row total"><span>Tổng cộng</span><b>{{ number_format($order->total_amount,0,',','.') }}đ</b></div>
        <form method="post" action="{{ route('account.orders.reorder', $order->code) }}">@csrf<button class="button primary full">Mua lại đơn hàng</button></form>
        @if($order->status === 'cancelled')
            <div class="cancel-info"><b>Đơn đã hủy{{ $order->cancelled_at ? ' lúc '.$order->cancelled_at->format('H:i d/m/Y') : '' }}</b>@if($order->cancel_reason)<p>Lý do: {{ $order->cancel_reason }}</p>@endif</div>
        @elseif($order->canBeCancelledByCustomer())
            <details class="cancel-order" id="huy-don" @if($errors->has('reason') || request()->query('huy')) open @endif>
                <summary>Hủy đơn hàng</summary>
                <form method="post" action="{{ route('account.orders.cancel', $order->code) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn {{ $order->code }}?')">@csrf
                    <fieldset><legend>Vì sao bạn muốn hủy đơn?</legend>
                        @foreach(\App\Models\Order::CANCEL_REASONS as $reason)
                            <label><input type="radio" name="reason" value="{{ $reason }}" @checked(old('reason') === $reason) required> {{ $reason }}</label>
                        @endforeach
                    </fieldset>
                    <label class="sr-only" for="reason_detail">Chi tiết thêm</label>
                    <textarea id="reason_detail" name="reason_detail" maxlength="200" placeholder="Chi tiết thêm (không bắt buộc)">{{ old('reason_detail') }}</textarea>
                    @error('reason')<small class="field-error">{{ $message }}</small>@enderror
                    <button class="button danger full">Xác nhận hủy đơn</button>
                </form>
            </details>
        @elseif(! in_array($order->status, ['completed'], true))
            <p class="cancel-note">{{ $order->payment_status === 'paid' ? 'Đơn đã thanh toán online. Để hủy và hoàn tiền, vui lòng gọi hotline 1900 6868.' : 'Đơn đang được xử lý/giao nên không thể hủy. Cần hỗ trợ, vui lòng gọi 1900 6868.' }}</p>
        @endif
    </aside>
</div>
@endsection
