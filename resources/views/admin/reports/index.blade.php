@extends('admin.layouts.app')
@section('title','Báo cáo mặt hàng & tồn kho')
@section('heading','Báo cáo mặt hàng & tồn kho')
@section('content')
<div class="admin-page-head">
    <div><h1>Báo cáo mặt hàng & tồn kho</h1><p>Chi tiết doanh thu, số lượng theo mặt hàng, sản phẩm bán chạy và tồn kho.</p></div>
    <form class="admin-filter report-filter" method="get" action="{{ route('admin.reports.index') }}">
        <label for="report-month">Tháng báo cáo</label>
        <input id="report-month" type="month" name="month" value="{{ $month->format('Y-m') }}" required>
        <button class="admin-btn primary">Xem báo cáo</button>
    </form>
</div>

<div class="stat-grid admin-stat-grid report-stat-grid">
    <article class="stat-card"><span>Doanh thu tháng</span><strong>{{ number_format((float) $summary->revenue,0,',','.') }}đ</strong><small>Đơn đã hoàn tất, gồm phí giao hàng</small></article>
    <article class="stat-card"><span>Đơn hoàn tất</span><strong>{{ number_format((int) $summary->order_count,0,',','.') }}</strong><small>Trong tháng {{ $month->format('m/Y') }}</small></article>
    <article class="stat-card"><span>Mặt hàng đã bán</span><strong>{{ number_format((float) $itemSales->sum('quantity_sold'),0,',','.') }}</strong><small>Tổng số lượng trên đơn hoàn tất</small></article>
</div>

<section class="admin-panel report-panel">
    <div class="panel-head"><div><h2>Doanh thu chi tiết theo mặt hàng</h2><small>Giá trị hàng hóa theo dòng đơn; lưu tên và SKU tại thời điểm đặt hàng.</small></div><strong>{{ $month->format('m/Y') }}</strong></div>
    <div class="table-wrap"><table><thead><tr><th>#</th><th>Mặt hàng</th><th>SKU</th><th>Số đơn</th><th>Số lượng bán</th><th>Doanh thu mặt hàng</th></tr></thead><tbody>
    @forelse($itemSales as $index => $item)
        <tr><td>{{ $index + 1 }}</td><td><b>{{ $item->product_name }}</b></td><td>{{ $item->sku }}</td><td>{{ number_format((int) $item->order_count,0,',','.') }}</td><td>{{ number_format((float) $item->quantity_sold,3,',','.') }}</td><td><b>{{ number_format((float) $item->item_revenue,0,',','.') }}đ</b></td></tr>
    @empty
        <tr><td colspan="6" class="empty-cell">Tháng này chưa có đơn hàng hoàn tất.</td></tr>
    @endforelse
    </tbody></table></div>
</section>

<div class="dashboard-grid report-lists">
    <section class="admin-panel"><div class="panel-head"><div><h2>Sản phẩm bán chạy</h2><small>Xếp theo số lượng bán trong {{ $month->format('m/Y') }}</small></div></div>
        <div class="table-wrap"><table><thead><tr><th>Mặt hàng</th><th>Đã bán</th><th>Doanh thu hàng</th></tr></thead><tbody>
        @forelse($bestSellers as $item)<tr><td><b>{{ $item->product_name }}</b><small class="block">{{ $item->sku }}</small></td><td>{{ number_format((float) $item->quantity_sold,3,',','.') }}</td><td>{{ number_format((float) $item->item_revenue,0,',','.') }}đ</td></tr>
        @empty<tr><td colspan="3" class="empty-cell">Chưa có dữ liệu bán hàng.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="admin-panel"><div class="panel-head"><div><h2>Tồn kho nhiều nhất</h2><small>Số lượng khả dụng hiện tại, đã trừ hàng giữ chỗ</small></div></div>
        <div class="table-wrap"><table><thead><tr><th>Mặt hàng</th><th>SKU</th><th>Tồn khả dụng</th></tr></thead><tbody>
        @forelse($stockProducts as $product)<tr><td><a href="{{ route('admin.products.edit',$product->id) }}"><b>{{ $product->name }}</b></a></td><td>{{ $product->sku }}</td><td><b>{{ number_format((float) $product->available_stock,3,',','.') }} {{ $units[$product->unit_id] ?? '' }}</b></td></tr>
        @empty<tr><td colspan="3" class="empty-cell">Chưa có sản phẩm đang bán.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
