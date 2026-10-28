<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — MiniMart</title>
    @vite(['resources/css/app.css'])
</head>
<body class="error-body">
    <main class="error-page">
        <a class="brand" href="{{ url('/') }}" aria-label="MiniMart - trang chủ"><span class="brand-mark" aria-hidden="true">M</span><span>Mini<span>Mart</span></span></a>
        <div class="error-card">
            <div class="error-art" aria-hidden="true">
                <svg viewBox="0 0 200 150">
                    <ellipse cx="100" cy="132" rx="62" ry="8" fill="#dcebe4"/>
                    <path d="M42 58h116l-12 64a8 8 0 0 1-8 6H62a8 8 0 0 1-8-6z" fill="#057a55"/>
                    <path d="M42 58h116" stroke="#10251d" stroke-width="6" stroke-linecap="round"/>
                    <path d="M70 58c0-22 13-36 30-36s30 14 30 36" fill="none" stroke="#10251d" stroke-width="6" stroke-linecap="round"/>
                    <path d="M72 86q4-6 8 0M120 86q4-6 8 0" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
                    <path d="M88 108q12-8 24 0" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="160" cy="30" r="14" fill="#f2b54a"/><text x="160" y="36" text-anchor="middle" font-family="'Be Vietnam Pro',sans-serif" font-weight="700" font-size="17" fill="#fff">!</text>
                </svg>
            </div>
            <span class="error-code">@yield('code')</span>
            <h1>@yield('heading')</h1>
            <p>@yield('message')</p>
            <div class="error-actions">
                @hasSection('action')@yield('action')@else<a class="button primary" href="{{ url('/') }}">Về trang chủ</a>@endif
                <a class="button ghost" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Quay lại trang trước</a>
            </div>
            <small>Cần hỗ trợ? Gọi hotline <b>1900 6868</b> (07:00–22:00).</small>
        </div>
    </main>
</body>
</html>
