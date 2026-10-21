<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','Quản trị') — MiniMart</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="admin-body"><a class="skip-link" href="#admin-main">Đi tới nội dung</a><div class="admin-shell">
<aside class="admin-sidebar"><a class="brand admin-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">M</span><span>Mini<span>Mart</span></span></a><p>Trung tâm vận hành</p><nav aria-label="Quản trị">
<a class="{{ request()->routeIs('admin.dashboard')?'active':'' }}" href="{{ route('admin.dashboard') }}">Tổng quan</a>
<a class="{{ request()->routeIs('admin.categories.*')?'active':'' }}" href="{{ route('admin.categories.index') }}">Danh mục</a>
<a class="{{ request()->routeIs('admin.products.*')?'active':'' }}" href="{{ route('admin.products.index') }}">Sản phẩm</a>
<a class="{{ request()->routeIs('admin.orders.*')?'active':'' }}" href="{{ route('admin.orders.index') }}">Đơn hàng</a>
</nav><div class="admin-sidebar-footer"><a href="{{ route('home') }}">← Xem cửa hàng</a><form method="post" action="{{ route('logout') }}">@csrf<button>Đăng xuất</button></form></div></aside>
<main id="admin-main" class="admin-main"><header class="admin-topbar"><button class="admin-menu" type="button" aria-label="Mở menu" onclick="document.body.classList.toggle('admin-nav-open')">☰</button><div><b>@yield('heading','Quản trị')</b><small>{{ auth()->user()->name }} · {{ auth()->user()->role?->name }}</small></div></header>
<div class="admin-content">@if(session('success'))<div class="admin-alert success" role="status">{{ session('success') }}</div>@endif @if($errors->any())<div class="admin-alert danger" role="alert"><b>Cần kiểm tra lại:</b> {{ $errors->first() }}</div>@endif @yield('content')</div></main></div></body></html>
