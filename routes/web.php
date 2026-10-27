<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\VnpayController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\RescueStoreController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CookingAssistantController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RescueController as AdminRescueController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/san-pham', [StoreController::class, 'products'])->name('products.index');
Route::get('/san-pham/{product:slug}', [StoreController::class, 'show'])->name('products.show');
Route::get('/giai-cuu-thuc-pham', [RescueStoreController::class, 'index'])->name('rescue.index');
Route::get('/tro-ly-nau-an', [CookingAssistantController::class, 'index'])->name('cooking-assistant.index');
Route::post('/tro-ly-nau-an/hoi', [CookingAssistantController::class, 'ask'])->middleware('throttle:10,1')->name('cooking-assistant.ask');
Route::post('/tro-ly-nau-an/them-gio-hang', [CookingAssistantController::class, 'addProducts'])->middleware('throttle:20,1')->name('cooking-assistant.add-products');
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/gio-hang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang/{product}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/thanh-toan', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/thanh-toan/vnpay/return', [VnpayController::class, 'return'])->name('vnpay.return');
Route::get('/thanh-toan/vnpay/ipn', [VnpayController::class, 'ipn'])->name('vnpay.ipn');
Route::get('/dat-hang-thanh-cong/{order:code}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/api/dia-chi/tinh-thanh', [AddressController::class, 'provinces'])->name('address.provinces');
Route::get('/api/dia-chi/tinh-thanh/{provinceCode}/phuong-xa', [AddressController::class, 'wards'])->name('address.wards');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/dang-nhap/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/dang-nhap/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:10,1')->name('auth.google.callback');
    Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/quen-mat-khau', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [AuthController::class, 'resetPassword'])->name('password.update');
});
Route::post('/dang-xuat', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('tai-khoan')->name('account.')->middleware('auth')->group(function () {
    Route::get('/ho-so', [AccountController::class, 'profile'])->name('profile');
    Route::patch('/ho-so', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/don-hang', [AccountController::class, 'orders'])->name('orders.index');
    Route::get('/don-hang/{code}', [AccountController::class, 'showOrder'])->name('orders.show');
    Route::post('/don-hang/{code}/mua-lai', [AccountController::class, 'reorder'])->name('orders.reorder');
    Route::post('/danh-gia/{item}', [ReviewController::class, 'store'])->name('reviews.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin,manager,staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', AdminCategoryController::class)->except('show');
    Route::post('categories/{category}/restore', [AdminCategoryController::class, 'restore'])->name('categories.restore');
    Route::resource('products', AdminProductController::class)->except('show');
    Route::post('products/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
    Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::patch('products/{product}/images/{image}/primary', [AdminProductController::class, 'setPrimaryImage'])->name('products.images.primary');
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::get('giai-cuu', [AdminRescueController::class, 'index'])->name('rescue.index');
    Route::post('giai-cuu/lo-hang/{lot}', [AdminRescueController::class, 'store'])->name('rescue.store');
    Route::patch('giai-cuu/{campaign}/dung', [AdminRescueController::class, 'stop'])->name('rescue.stop');
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('users', AdminUserController::class)->except(['show', 'destroy']);
    });
});
