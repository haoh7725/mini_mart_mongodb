<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MiniMart - thực phẩm tươi ngon, giao nhanh mỗi ngày">
    <title>@yield('title', 'MiniMart') — Tươi ngon mỗi ngày</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Đi tới nội dung chính</a>
    <div class="topbar">Giao nhanh trong 2 giờ <span>•</span> Miễn phí từ 300.000đ <span>•</span> Hotline 1900 6868</div>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="MiniMart - trang chủ">
                <span class="brand-mark" aria-hidden="true">M</span><span>Mini<span>Mart</span></span>
            </a>
            <form class="search" action="{{ route('products.index') }}" role="search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                <label class="sr-only" for="site-search">Tìm kiếm sản phẩm</label>
                <input id="site-search" name="q" value="{{ request('q') }}" placeholder="Bạn muốn mua gì hôm nay?">
                <button>Tìm kiếm</button>
            </form>
            <nav class="header-actions" aria-label="Tiện ích">
                <a href="{{ route('products.index') }}">Cửa hàng</a>
                <a class="rescue-nav-link" href="{{ route('rescue.index') }}">Sales</a>
            
                <a class="cart-link" href="{{ route('cart.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 3h2l2.3 10.1a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6m4 12a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                    Giỏ hàng <b>{{ array_sum(session('cart', [])) }}</b>
                </a>
                
                @auth
                    @if(auth()->user()->hasRole('super-admin', 'manager', 'staff'))<a href="{{ route('admin.dashboard') }}">Quản trị</a>@endif
                    <details class="profile-menu">
                        <summary aria-label="Mở menu tài khoản">
                            <span class="profile-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="profile-trigger-name">{{ auth()->user()->name }}</span>
                            <span aria-hidden="true">⌄</span>
                        </summary>
                        <div class="profile-dropdown">
                            <div class="profile-card-head">
                                <span class="profile-avatar large">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div>
                            </div>
                            <nav aria-label="Menu tài khoản">
                                <a href="{{ route('account.orders.index') }}"><span class="profile-menu-icon">▣</span><span><b>Đơn đặt hàng của tôi</b><small>Xem và theo dõi đơn đã mua</small></span></a>
                                <a href="{{ route('account.profile') }}"><span class="profile-menu-icon">♙</span><span><b>Hồ sơ của tôi</b><small>Thông tin và tùy chọn cá nhân</small></span></a>
                                <a href="{{ route('account.profile') }}#doi-mat-khau"><span class="profile-menu-icon">▢</span><span><b>Đổi mật khẩu</b><small>Bảo mật tài khoản của bạn</small></span></a>
                            </nav>
                            <form class="profile-logout" action="{{ route('logout') }}" method="post">@csrf<button><span class="profile-menu-icon">→</span>Đăng xuất</button></form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}">Đăng nhập</a>
                @endauth
                
            </nav>
        </div>
    </header>

    <x-flash-messages />
    <x-confirm-dialog />

    <main id="main">@yield('content')</main>

    <footer class="footer">
        <div class="container footer-grid">
            <div><div class="brand brand-footer"><span class="brand-mark">M</span><span>Mini<span>Mart</span></span></div><p>Người bạn đồng hành cho căn bếp Việt — tươi ngon, minh bạch và tiện lợi.</p></div>
            <div><h3>Mua sắm</h3><a href="{{ route('products.index') }}">Tất cả sản phẩm</a><a href="{{ route('products.index', ['sort' => 'price_asc']) }}">Giá tốt hôm nay</a></div>
            <div><h3>Hỗ trợ</h3><p>1900 6868</p><p>support@minimart.test</p><p>07:00–22:00 mỗi ngày</p></div>
        </div>
        <div class="container footer-bottom">© {{ date('Y') }} MiniMart. Dự án Laravel thương mại điện tử.</div>
    </footer>
    <x-ai-chatbox />
    <script>
        document.addEventListener('click', event => {
            document.querySelectorAll('.profile-menu[open]').forEach(menu => {
                if (!menu.contains(event.target)) menu.removeAttribute('open');
            });
        });
    </script>
</body>
</html>
