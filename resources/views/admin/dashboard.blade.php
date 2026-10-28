@extends('admin.layouts.app')
@section('title','Tổng quan')
@section('heading','Tổng quan vận hành')
@section('content')
@php($statusLabels=\App\Models\Order::STATUS_LABELS)
<div class="admin-page-head"><div><span class="eyebrow">{{ now()->format('d/m/Y') }}</span><h1>Bảng điều khiển</h1><p>Theo dõi doanh thu, đơn hàng, tồn kho và khách hàng trong một nơi.</p></div><div class="actions"><a class="admin-btn" href="{{ route('admin.orders.index') }}">Xử lý đơn</a><a class="admin-btn primary" href="{{ route('admin.products.create') }}">+ Thêm sản phẩm</a></div></div>
<div class="stat-grid admin-stat-grid">
@foreach([['Doanh thu',number_format($stats['revenue'],0,',','.').'đ','Đơn hoàn tất'],['Đơn hôm nay',$stats['today_orders'],'Trong ngày'],['Chờ xác nhận',$stats['pending_orders'],'Cần xử lý'],['Sản phẩm',$stats['products'],'Đang quản lý'],['Khách hàng',$stats['customers'],'Tài khoản'],['Sắp hết hàng',$stats['low_stock'],'Tồn ≤ 10']] as [$label,$value,$hint])<article class="stat-card"><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $hint }}</small></article>@endforeach
</div>
<div class="dashboard-grid">
    <section class="admin-panel"><div class="panel-head"><div><h2>Doanh thu 6 tháng</h2><small>Chỉ tính đơn đã hoàn tất</small></div></div><div class="revenue-chart">@foreach($monthlyRevenue as $month)<div class="chart-column"><strong>{{ $month['value'] ? number_format($month['value']/1000000,1,',','.').'tr' : '0' }}</strong><div><span style="height:{{ max(4, ($month['value']/$chartMax)*100) }}%"></span></div><small>{{ $month['label'] }}</small></div>@endforeach</div></section>
    <section class="admin-panel"><div class="panel-head"><h2>Trạng thái đơn hàng</h2></div><div class="status-summary">@foreach($statusLabels as $key=>$label)<div><span><i class="status-dot status-dot-{{ $key }}"></i>{{ $label }}</span><strong>{{ $orderStatuses[$key] ?? 0 }}</strong></div>@endforeach</div></section>
</div>
<div class="dashboard-grid dashboard-bottom">
    <section class="admin-panel"><div class="panel-head"><h2>Đơn hàng gần đây</h2><a href="{{ route('admin.orders.index') }}">Xem tất cả</a></div><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Trạng thái</th><th>Tổng tiền</th><th></th></tr></thead><tbody>@forelse($recentOrders as $order)<tr><td><b>{{ $order->code }}</b></td><td>{{ $order->customer_name }}</td><td><span class="status status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td><td>{{ number_format($order->total_amount,0,',','.') }}đ</td><td><a href="{{ route('admin.orders.show',$order) }}">Chi tiết</a></td></tr>@empty<tr><td colspan="5" class="empty-cell">Chưa có đơn hàng.</td></tr>@endforelse</tbody></table></div></section>
    <section class="admin-panel"><div class="panel-head"><h2>Cảnh báo tồn kho</h2><a href="{{ route('admin.products.index') }}">Kho hàng</a></div><div class="stock-alert-list">@forelse($lowStockProducts as $product)<a href="{{ route('admin.products.edit',$product) }}"><span>{{ mb_substr($product->name,0,1) }}</span><div><b>{{ $product->name }}</b><small>{{ $product->sku }}</small></div><strong>{{ number_format($product->stock,0,',','.') }}</strong></a>@empty<p class="empty-cell">Không có sản phẩm sắp hết.</p>@endforelse</div></section>
</div>
@endsection
