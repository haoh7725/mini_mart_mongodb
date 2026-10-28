@extends('admin.layouts.app') @section('title',$order->code) @section('heading','Chi tiết đơn hàng')
@section('content')<div class="admin-page-head"><div><span class="eyebrow">{{ $order->code }}</span><h1>{{ $order->customer_name }}</h1><p>{{ $order->customer_phone }} · {{ $order->customer_email ?: 'Không có email' }}</p><x-order-discount :order="$order" /></div><a class="admin-btn" href="{{ route('admin.orders.index') }}">← Danh sách</a></div>
<div class="order-admin-grid"><div class="order-main">
<section class="admin-panel"><div class="panel-head"><h2>Sản phẩm</h2><span class="status status-{{ $order->status }}">{{ $order->statusLabel() }}</span></div><div class="table-wrap"><table><thead><tr><th>Tên</th><th>SKU</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->sku }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price,0,',','.') }}đ</td><td>{{ number_format($item->line_total,0,',','.') }}đ</td></tr>@endforeach</tbody></table></div></section>
@if($order->returnRequest)
<section class="admin-panel return-panel"><div class="panel-head"><h2>Yêu cầu trả hàng/hoàn tiền</h2><span class="status status-return-{{ $order->returnRequest->status }}">{{ \App\Models\ReturnRequest::STATUS_LABELS[$order->returnRequest->status] }}</span></div>
<div class="return-body"><p><b>Lý do:</b> {{ $order->returnRequest->reason }}</p>@if($order->returnRequest->detail)<p><b>Mô tả của khách:</b> {{ $order->returnRequest->detail }}</p>@endif<p><small>Gửi lúc {{ $order->returnRequest->created_at->format('H:i d/m/Y') }}</small></p>
@if($order->returnRequest->status === 'pending')
<form method="post" action="{{ route('admin.orders.return', $order) }}" class="return-decision">@csrf @method('patch')<div class="field"><label for="admin_note">Phản hồi cho khách</label><textarea id="admin_note" name="admin_note" maxlength="500" placeholder="Bắt buộc khi từ chối, ví dụ: ảnh chụp cho thấy hàng còn nguyên vẹn">{{ old('admin_note') }}</textarea>@error('admin_note')<small>{{ $message }}</small>@enderror @error('return')<small>{{ $message }}</small>@enderror</div>
<p class="field-hint">Chấp nhận sẽ chuyển đơn sang "Trả hàng/Hoàn tiền"{{ $order->payment_status === 'paid' ? ' và ghi nhận hoàn '.number_format($order->total_amount,0,',','.').'đ' : '' }}. Hàng trả về không tự nhập lại kho.</p>
<div class="form-actions"><button class="admin-btn danger" name="decision" value="reject">Từ chối</button><button class="admin-btn primary" name="decision" value="approve">Chấp nhận hoàn tiền</button></div></form>
@else
@if($order->returnRequest->admin_note)<p><b>Phản hồi của shop:</b> {{ $order->returnRequest->admin_note }}</p>@endif @if($order->returnRequest->refund_amount > 0)<p><b>Đã hoàn:</b> {{ number_format($order->returnRequest->refund_amount,0,',','.') }}đ</p>@endif
@endif</div></section>
@endif
<section class="admin-panel"><div class="panel-head"><h2>Hành trình đơn hàng</h2>@if($order->estimated_delivery_at && $order->isInDelivery())<small>Dự kiến giao trước {{ $order->estimated_delivery_at->format('H:i d/m') }}</small>@endif</div>
<x-order-timeline :order="$order" show-creator class="admin" />
@unless($order->isClosed())
<form method="post" action="{{ route('admin.orders.tracking', $order) }}" class="tracking-form">@csrf<div class="field"><label for="title">Thêm cập nhật cho khách</label><input id="title" name="title" maxlength="120" required list="tracking-titles" placeholder="Ví dụ: Shipper đang ở Quận 7" value="{{ old('title') }}"><datalist id="tracking-titles"><option value="Shipper đang trên đường giao"><option value="Shipper sắp tới, vui lòng để ý điện thoại"><option value="Đơn hàng bị chậm do thời tiết"></datalist>@error('title')<small>{{ $message }}</small>@enderror</div><div class="field"><label for="description">Chi tiết (không bắt buộc)</label><input id="description" name="description" maxlength="500" value="{{ old('description') }}"></div><button class="admin-btn">Thêm vào hành trình</button></form>
@endunless
</section></div>
<aside class="admin-panel order-side"><h2>Xử lý đơn</h2><p><b>Giao tới:</b><br>{{ $order->shipping_address }}</p>@if($order->customer_note)<p><b>Ghi chú:</b><br>{{ $order->customer_note }}</p>@endif @if($order->status==='cancelled' && $order->cancel_reason)<p class="cancel-info"><b>Đã hủy{{ $order->cancelled_at ? ' lúc '.$order->cancelled_at->format('H:i d/m/Y') : '' }}:</b><br>{{ $order->cancel_reason }}</p>@endif
@if($order->isClosed())
<p class="field-hint">Đơn đã {{ $order->status === 'cancelled' ? 'hủy' : 'trả hàng/hoàn tiền' }}, không cập nhật thêm được.</p>
@else
<form method="post" action="{{ route('admin.orders.update',$order) }}">@csrf @method('patch')
<div class="field"><label for="status">Trạng thái đơn</label><select id="status" name="status">@foreach($manualStatuses as $value)<option value="{{ $value }}" @selected($order->status===$value)>{{ \App\Models\Order::STATUS_LABELS[$value] }}</option>@endforeach</select>@error('status')<small class="field-error">{{ $message }}</small>@enderror</div>
<div class="field"><label for="shipper_id">Shipper giao hàng</label><select id="shipper_id" name="shipper_id"><option value="">Chưa phân công</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected($order->shipper_id === $shipper->id)>{{ $shipper->name }}{{ $shipper->phone ? ' · '.$shipper->phone : '' }}</option>@endforeach</select>@if($shippers->isEmpty())<small class="field-hint">Chưa có tài khoản shipper. Tạo ở mục Tài khoản với vai trò Shipper.</small>@endif</div>
<div class="field"><label for="payment_status">Thanh toán</label><select id="payment_status" name="payment_status">@foreach(\App\Models\Order::PAYMENT_LABELS as $value=>$label)<option value="{{ $value }}" @selected($order->payment_status===$value)>{{ $label }}</option>@endforeach</select></div>
<button class="admin-btn primary full">Cập nhật</button></form>
@endif
<div class="order-total"><span>Tổng thanh toán</span><strong>{{ number_format($order->total_amount,0,',','.') }}đ</strong></div></aside></div>@endsection
