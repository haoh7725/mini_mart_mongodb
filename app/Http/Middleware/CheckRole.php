<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->hasRole(...$roles), 403, 'Bạn không có quyền truy cập khu vực này.');

        return $next($request);
    }
}
