<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','Quản trị') — MiniMart</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="admin-body"><a class="skip-link" href="#admin-main">Đi tới nội dung</a><div class="admin-shell">
<aside class="admin-sidebar"><a class="brand admin-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">M</span><span>Mini<span>Mart</span></span></a><p>Trung tâm vận hành</p><nav aria-label="Quản trị">
<a class="{{ request()->routeIs('admin.dashboard')?'active':'' }}" href="{{ route('admin.dashboard') }}">Tổng quan</a>
<a class="{{ request()->routeIs('admin.categories.*')?'active':'' }}" href="{{ route('admin.categories.index') }}">Danh mục</a>
<a class="{{ request()->routeIs('admin.products.*')?'active':'' }}" href="{{ route('admin.products.index') }}">Sản phẩm</a>
<a class="{{ request()->routeIs('admin.orders.*')?'active':'' }}" href="{{ route('admin.orders.index') }}">Đơn hàng</a>
<a class="{{ request()->routeIs('admin.rescue.*')?'active':'' }}" href="{{ route('admin.rescue.index') }}">BIG SALES</a>
<a class="{{ request()->routeIs('admin.reviews.*')?'active':'' }}" href="{{ route('admin.reviews.index') }}">Đánh giá</a>
@if(auth()->user()->hasRole('super-admin'))<a class="{{ request()->routeIs('admin.users.*')?'active':'' }}" href="{{ route('admin.users.index') }}">Tài khoản & phân quyền</a>@endif
</nav><div class="admin-sidebar-footer"><a href="{{ route('home') }}">← Xem cửa hàng</a><form method="post" action="{{ route('logout') }}">@csrf<button>Đăng xuất</button></form></div></aside>
<main id="admin-main" class="admin-main"><header class="admin-topbar"><button class="admin-menu" type="button" aria-label="Mở menu" onclick="document.body.classList.toggle('admin-nav-open')">☰</button><div><b>@yield('heading','Quản trị')</b><small>{{ auth()->user()->name }} · {{ auth()->user()->role?->name }}</small></div></header>
<div class="admin-content">@yield('content')</div></main></div><x-flash-messages /><x-confirm-dialog /></body></html>
