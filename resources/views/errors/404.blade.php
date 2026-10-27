@extends('errors.layout')
@section('title', 'Không tìm thấy trang')
@section('code', '404')
@section('heading', 'Ối! Trang này đã “hết hàng”')
@section('message')Trang bạn tìm có thể đã bị đổi tên, tạm ẩn hoặc không còn tồn tại. Thử tìm sản phẩm khác trong cửa hàng nhé.@endsection
@section('action')<a class="button primary" href="{{ route('products.index') }}">Xem cửa hàng</a>@endsection
