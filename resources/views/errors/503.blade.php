@extends('errors.layout')
@section('title', 'Đang bảo trì')
@section('code', '503')
@section('heading', 'MiniMart đang bảo trì')
@section('message'){{ $exception->getMessage() ?: 'Chúng tôi đang nâng cấp hệ thống để phục vụ bạn tốt hơn. Vui lòng quay lại sau ít phút.' }}@endsection
