<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // abort(422, '...') trên trang thường: quay lại trang trước và hiện toast lỗi thay vì trang lỗi trắng.
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 422 || $request->expectsJson() || $exception->getMessage() === '') {
                return null;
            }
            $fallback = url()->previous() !== $request->fullUrl() ? url()->previous() : url('/');

            return redirect()->to($fallback)->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->withErrors(['message' => $exception->getMessage()]);
        });
    })->create();
