@extends('layouts.app')
@section('title', 'Đơn hàng đã mua')
@section('content')
@php($statuses = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','processing'=>'Đang xử lý','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'])
<section class="page-hero compact"><div class="container"><span class="eyebrow">Tài khoản</span><h1>Đơn hàng đã mua</h1></div></section>
<div class="container account-layout">
    <nav class="account-nav" aria-label="Tài khoản"><a href="{{ route('account.profile') }}">Hồ sơ</a><a class="active" href="{{ route('account.orders.index') }}">Đơn hàng đã mua</a></nav>
    <section class="account-panel">
        <div class="panel-head"><div><h2>Lịch sử mua hàng</h2><p>Theo dõi trạng thái và xem lại sản phẩm đã đặt.</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Sản phẩm</th><th>Trạng thái</th><th>Thanh toán</th><th>Tổng tiền</th></tr></thead><tbody>
        @forelse($orders as $order)
            <tr><td><a href="{{ route('account.orders.show', $order->code) }}"><b>{{ $order->code }}</b></a></td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td><td>{{ $order->items_count }}</td><td><span class="status status-{{ $order->status }}">{{ $statuses[$order->status] ?? $order->status }}</span></td><td>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</td><td><b>{{ number_format($order->total_amount, 0, ',', '.') }}đ</b></td></tr>
        @empty
            <tr><td colspan="6" class="empty-cell">Bạn chưa có đơn hàng nào. <a href="{{ route('products.index') }}">Mua sắm ngay</a></td></tr>
        @endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $orders->links() }}</div>
    </section>
</div>
@endsection
