<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MiniMart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 42rem">
            <div class="card-body p-5 text-center">
                <span class="badge text-bg-success mb-3">Laravel + Bootstrap 5</span>
                <h1 class="display-5 fw-bold">MiniMart</h1>
                <p class="lead text-secondary">Cửa hàng mini với giao diện Bootstrap responsive.</p>
                <a class="btn btn-success btn-lg" href="{{ route('home') }}">Vào cửa hàng</a>
            </div>
        </div>
    </main>
</body>
</html>
