@extends('layouts.app')
@section('title', 'Đơn hàng '.$order->code)
@section('content')
<div class="container"><x-order-discount :order="$order" /></div>
@php($step = ['pending' => 0, 'confirmed' => 1, 'processing' => 1, 'shipping' => 2, 'completed' => 3, 'returned' => 3][$order->status] ?? 0)
<section class="page-hero compact"><div class="container"><a class="eyebrow" href="{{ route('account.orders.index') }}">← Đơn hàng đã mua</a><h1>{{ $order->code }}</h1></div></section>
<div class="container account-order-grid">
    <div class="order-main">
    <section class="account-panel order-tracking" aria-labelledby="tracking-title">
        <div class="panel-head"><h2 id="tracking-title">Theo dõi đơn hàng</h2><span class="status status-{{ $order->status }}">{{ $order->statusLabel() }}</span></div>
        @if($order->status === 'cancelled')
            <p class="tracking-banner cancelled">Đơn hàng đã bị hủy{{ $order->cancelled_at ? ' lúc '.$order->cancelled_at->format('H:i d/m/Y') : '' }}.</p>
        @else
            <ol class="order-stepper">
                @foreach(['Đặt hàng', 'Đã xác nhận', 'Đang giao', $order->status === 'returned' ? 'Trả hàng/Hoàn tiền' : 'Đã giao'] as $index => $label)
                    <li @class(['done' => $index <= $step, 'current' => $index === $step])><span>{{ $index < $step || $order->status === 'completed' ? '✓' : $index + 1 }}</span>{{ $label }}</li>
                @endforeach
            </ol>
            @if($order->isInDelivery() && $order->estimated_delivery_at)
                <p class="tracking-banner">🚚 Giao nhanh trong {{ \App\Models\Order::DELIVERY_HOURS }} giờ · Dự kiến nhận hàng trước <b>{{ $order->estimated_delivery_at->format('H:i') }} {{ $order->estimated_delivery_at->isToday() ? 'hôm nay' : $order->estimated_delivery_at->format('d/m/Y') }}</b></p>
            @elseif($order->status === 'pending')
                <p class="tracking-banner">Shop sẽ xác nhận đơn sớm. Sau khi xác nhận, shipper của MiniMart giao trong khoảng {{ \App\Models\Order::DELIVERY_HOURS }} giờ.</p>
            @elseif($order->delivered_at)
                <p class="tracking-banner done">Đã giao lúc {{ $order->delivered_at->format('H:i d/m/Y') }}.</p>
            @endif
            @if($order->shipper && $order->isInDelivery())
                <div class="shipper-card"><span class="shipper-avatar" aria-hidden="true">{{ mb_substr($order->shipper->name, 0, 1) }}</span><div><small>Shipper của MiniMart</small><b>{{ $order->shipper->name }}</b></div>@if($order->shipper->phone)<a class="button ghost" href="tel:{{ $order->shipper->phone }}">Gọi {{ $order->shipper->phone }}</a>@endif</div>
            @endif
        @endif
        <x-order-timeline :order="$order" />
    </section>
    <section class="account-panel">
        <div class="panel-head"><h2>Sản phẩm</h2></div>
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
    </div>
    <aside class="account-panel order-detail">
        <h2>Thông tin đơn hàng</h2><dl><dt>Người nhận</dt><dd>{{ $order->customer_name }} · {{ $order->customer_phone }}</dd><dt>Địa chỉ</dt><dd>{{ $order->shipping_address }}</dd><dt>Phương thức</dt><dd>{{ ($payment->method ?? 'cod') === 'online' ? 'VNPay' : 'COD' }}</dd><dt>Thanh toán</dt><dd>{{ $order->paymentLabel() }}</dd>@if($order->customer_note)<dt>Ghi chú</dt><dd>{{ $order->customer_note }}</dd>@endif</dl>
        <div class="price-row"><span>Tạm tính</span><b>{{ number_format($order->subtotal,0,',','.') }}đ</b></div><div class="price-row"><span>Phí giao hàng</span><b>{{ number_format($order->shipping_fee,0,',','.') }}đ</b></div><div class="price-row total"><span>Tổng cộng</span><b>{{ number_format($order->total_amount,0,',','.') }}đ</b></div>
        <form method="post" action="{{ route('account.orders.reorder', $order->code) }}">@csrf<button class="button primary full">Mua lại đơn hàng</button></form>
        @if($order->status === 'cancelled')
            <div class="cancel-info"><b>Đơn đã hủy{{ $order->cancelled_at ? ' lúc '.$order->cancelled_at->format('H:i d/m/Y') : '' }}</b>@if($order->cancel_reason)<p>Lý do: {{ $order->cancel_reason }}</p>@endif</div>
        @elseif($order->canBeCancelledByCustomer())
            <details class="cancel-order" id="huy-don" @if($errors->has('reason') || request()->query('huy')) open @endif>
                <summary>Hủy đơn hàng</summary>
                <form method="post" action="{{ route('account.orders.cancel', $order->code) }}" data-confirm="Đơn {{ $order->code }} sẽ bị hủy và sản phẩm được trả lại kho. Thao tác này không thể hoàn tác." data-confirm-title="Hủy đơn hàng này?" data-confirm-ok="Hủy đơn">@csrf
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
        @elseif($order->returnRequest)
            <div @class(['cancel-info', 'return-info', $order->returnRequest->status])><b>Yêu cầu trả hàng: {{ \App\Models\ReturnRequest::STATUS_LABELS[$order->returnRequest->status] }}</b><p>Lý do: {{ $order->returnRequest->reason }}{{ $order->returnRequest->detail ? ' – '.$order->returnRequest->detail : '' }}</p>@if($order->returnRequest->refund_amount > 0)<p>Số tiền hoàn: {{ number_format($order->returnRequest->refund_amount, 0, ',', '.') }}đ</p>@endif @if($order->returnRequest->admin_note)<p>Phản hồi của shop: {{ $order->returnRequest->admin_note }}</p>@endif</div>
        @elseif($order->canRequestReturn())
            <details class="cancel-order return-order" id="tra-hang" @if($errors->has('return_reason')) open @endif>
                <summary>Yêu cầu trả hàng/hoàn tiền</summary>
                <form method="post" action="{{ route('account.orders.return', $order->code) }}" data-confirm="Shop sẽ kiểm tra và phản hồi yêu cầu trả hàng của đơn {{ $order->code }}." data-confirm-title="Gửi yêu cầu trả hàng?" data-confirm-ok="Gửi yêu cầu">@csrf
                    <fieldset><legend>Sản phẩm gặp vấn đề gì?</legend>
                        @foreach(\App\Models\ReturnRequest::REASONS as $reason)
                            <label><input type="radio" name="return_reason" value="{{ $reason }}" @checked(old('return_reason') === $reason) required> {{ $reason }}</label>
                        @endforeach
                    </fieldset>
                    <label class="sr-only" for="return_detail">Mô tả thêm</label>
                    <textarea id="return_detail" name="return_detail" maxlength="500" placeholder="Mô tả thêm tình trạng sản phẩm (không bắt buộc)">{{ old('return_detail') }}</textarea>
                    @error('return_reason')<small class="field-error">{{ $message }}</small>@enderror
                    <button class="button primary full">Gửi yêu cầu</button>
                </form>
                <p class="cancel-note">Áp dụng trong {{ \App\Models\Order::RETURN_WINDOW_DAYS }} ngày kể từ khi nhận hàng.</p>
            </details>
        @elseif(! in_array($order->status, ['completed', 'returned'], true))
            <p class="cancel-note">{{ $order->payment_status === 'paid' ? 'Đơn đã thanh toán online. Để hủy và hoàn tiền, vui lòng gọi hotline 1900 6868.' : 'Đơn đang được xử lý/giao nên không thể hủy. Cần hỗ trợ, vui lòng gọi 1900 6868.' }}</p>
        @endif
    </aside>
</div>
@endsection
