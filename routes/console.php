<?php

use App\Services\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire-unpaid {--minutes=30}', function (OrderService $orders) {
    $count = $orders->expireUnpaidOnlinePayments((int) $this->option('minutes'));
    $this->info("Đã hủy {$count} đơn VNPay quá hạn thanh toán.");
})->purpose('Hủy và hoàn kho các đơn VNPay chưa thanh toán quá thời hạn');

Schedule::command('orders:expire-unpaid')->everyFiveMinutes()->withoutOverlapping();
