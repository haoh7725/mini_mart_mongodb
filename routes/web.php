<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/san-pham', [StoreController::class, 'products'])->name('products.index');
Route::get('/san-pham/{product:slug}', [StoreController::class, 'show'])->name('products.show');
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/gio-hang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang/{product}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/thanh-toan', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/dat-hang-thanh-cong/{order:code}', [CheckoutController::class, 'success'])->name('checkout.success');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/quen-mat-khau', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [AuthController::class, 'resetPassword'])->name('password.update');
});
Route::post('/dang-xuat', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin,manager,staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', AdminCategoryController::class)->except('show');
    Route::post('categories/{category}/restore', [AdminCategoryController::class, 'restore'])->name('categories.restore');
    Route::resource('products', AdminProductController::class)->except('show');
    Route::post('products/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
});
