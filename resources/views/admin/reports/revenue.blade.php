@extends('admin.layouts.app')
@section('title','Báo cáo doanh thu')
@section('heading','Báo cáo doanh thu')
@section('content')
<div class="admin-page-head">
    <div><h1>Báo cáo doanh thu</h1><p>Theo dõi doanh thu đơn hoàn tất theo tháng và theo ngày.</p></div>
    <form class="admin-filter report-filter" method="get" action="{{ route('admin.reports.revenue') }}">
        <label for="revenue-month">Tháng cần xem</label>
        <input id="revenue-month" type="month" name="month" value="{{ $month->format('Y-m') }}" required>
        <button class="admin-btn primary">Lọc doanh thu</button>
    </form>
</div>

<div class="stat-grid revenue-stat-grid">
    <article class="stat-card"><span>Doanh thu tháng</span><strong>{{ number_format((float) $summary->revenue,0,',','.') }}đ</strong><small>Đơn đã hoàn tất, gồm phí giao hàng · {{ $month->format('m/Y') }}</small></article>
    <article class="stat-card"><span>Đơn hoàn tất</span><strong>{{ number_format((int) $summary->order_count,0,',','.') }}</strong><small>Trong tháng được chọn</small></article>
    <article class="stat-card"><span>Doanh thu trung bình/đơn</span><strong>{{ number_format($summary->order_count ? $summary->revenue / $summary->order_count : 0,0,',','.') }}đ</strong><small>Doanh thu chia cho số đơn hoàn tất</small></article>
    <article class="stat-card"><span>Doanh thu thấp nhất</span>@if($lowestRevenueProduct)<strong>{{ number_format((float) $lowestRevenueProduct->item_revenue,0,',','.') }}đ</strong><small>{{ $lowestRevenueProduct->product_name }} · Trong số sản phẩm đã bán</small>@else<strong>—</strong><small>Chưa có sản phẩm bán trong tháng</small>@endif</article>
</div>

<section class="admin-panel revenue-history-panel">
    <div class="panel-head"><div><h2>Doanh thu 12 tháng gần nhất</h2><small>Chỉ tính đơn đã hoàn tất</small></div></div>
    <div class="revenue-chart revenue-chart-year">
        @foreach($monthlyRevenue as $period)
        <div class="chart-column"><strong>{{ $period['revenue'] ? number_format($period['revenue']/1000000,1,',','.').'tr' : '0' }}</strong><div><span style="height:{{ max(4, ($period['revenue']/$chartMax)*100) }}%"></span></div><small>{{ $period['label'] }}</small></div>
        @endforeach
    </div>
</section>

<section class="admin-panel daily-revenue-panel">
    <div class="panel-head"><div><h2>Doanh thu từng ngày</h2><small>Tháng {{ $month->format('m/Y') }}</small></div></div>
    <div class="table-wrap"><table><thead><tr><th>Ngày</th><th>Số đơn hoàn tất</th><th>Doanh thu</th></tr></thead><tbody>
        @forelse($dailyRevenue as $day)
        <tr><td>{{ \Carbon\Carbon::parse($day->day)->format('d/m/Y') }}</td><td>{{ number_format((int) $day->order_count,0,',','.') }}</td><td><b>{{ number_format((float) $day->revenue,0,',','.') }}đ</b></td></tr>
        @empty
        <tr><td colspan="3" class="empty-cell">Tháng này chưa có đơn hàng hoàn tất.</td></tr>
        @endforelse
    </tbody></table></div>
</section>

<div class="dashboard-grid revenue-breakdown-grid">
    <section class="admin-panel"><div class="panel-head"><div><h2>Sản phẩm được mua nhiều</h2><small>Xếp theo số lượng trong tháng {{ $month->format('m/Y') }}</small></div></div>
        <div class="table-wrap"><table><thead><tr><th>#</th><th>Sản phẩm</th><th>Đã bán</th><th>Tồn khả dụng</th><th>Tỷ lệ đánh giá</th><th>Doanh thu mặt hàng</th></tr></thead><tbody>
            @forelse($topProducts as $index => $product)
            <tr><td>{{ $index + 1 }}</td><td><b>{{ $product->product_name }}</b><small class="block">{{ $product->sku }}</small></td><td>{{ number_format((float) $product->quantity_sold,3,',','.') }}</td><td>{{ number_format((float) $product->available_stock,3,',','.') }} {{ $product->unit_symbol }}</td><td>{{ number_format((float) $product->response_rate,1,',','.') }}%</td><td><b>{{ number_format((float) $product->item_revenue,0,',','.') }}đ</b></td></tr>
            @empty<tr><td colspan="6" class="empty-cell">Tháng này chưa có sản phẩm bán ra.</td></tr>@endforelse
        </tbody></table></div>
        <p class="report-footnote">Tỷ lệ đánh giá = số dòng hàng đã mua có đánh giá / tổng số dòng hàng đã bán trong tháng.</p>
    </section>
    <section class="admin-panel"><div class="panel-head"><div><h2>Doanh thu theo nhóm hàng</h2><small>Phân loại theo danh mục sản phẩm</small></div></div>
        <div class="table-wrap"><table><thead><tr><th>Nhóm hàng</th><th>Số lượng bán</th><th>Doanh thu</th></tr></thead><tbody>
            @forelse($categoryRevenue as $category)
            <tr><td><b>{{ $category->category_name }}</b></td><td>{{ number_format((float) $category->quantity_sold,3,',','.') }}</td><td><b>{{ number_format((float) $category->item_revenue,0,',','.') }}đ</b></td></tr>
            @empty<tr><td colspan="3" class="empty-cell">Tháng này chưa có doanh thu theo nhóm hàng.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
