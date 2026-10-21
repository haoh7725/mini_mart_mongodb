@extends('admin.layouts.app') @section('title','Tổng quan') @section('heading','Tổng quan vận hành')
@section('content')
<div class="admin-page-head"><div><span class="eyebrow">Hôm nay</span><h1>Tình hình cửa hàng</h1><p>Theo dõi nhanh hàng hóa, khách hàng và đơn bán.</p></div><a class="admin-btn primary" href="{{ route('admin.products.create') }}">+ Thêm sản phẩm</a></div>
<div class="stat-grid">
@foreach([['Sản phẩm',$stats['products']],['Danh mục',$stats['categories']],['Đơn hàng',$stats['orders']],['Khách hàng',$stats['customers']],['Sắp hết hàng',$stats['low_stock']],['Doanh thu',number_format($stats['revenue'],0,',','.').'đ']] as [$label,$value])<article class="stat-card"><span>{{ $label }}</span><strong>{{ $value }}</strong></article>@endforeach
</div>
<section class="admin-panel"><div class="panel-head"><h2>Đơn hàng gần đây</h2><a href="{{ route('admin.orders.index') }}">Xem tất cả</a></div><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Trạng thái</th><th>Tổng tiền</th><th></th></tr></thead><tbody>@forelse($recentOrders as $order)<tr><td><b>{{ $order->code }}</b></td><td>{{ $order->customer_name }}</td><td><span class="status status-{{ $order->status }}">{{ $order->status }}</span></td><td>{{ number_format($order->total_amount,0,',','.') }}đ</td><td><a href="{{ route('admin.orders.show',$order) }}">Chi tiết</a></td></tr>@empty<tr><td colspan="5" class="empty-cell">Chưa có đơn hàng.</td></tr>@endforelse</tbody></table></div></section>
@endsection
