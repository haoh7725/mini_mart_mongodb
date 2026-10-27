<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;

class VnpayService
{
    public function configured(): bool
    {
        return filled(config('services.vnpay.tmn_code')) && filled(config('services.vnpay.hash_secret'));
    }

    public function paymentUrl(Order $order, Request $request): string
    {
        $now = now('Asia/Ho_Chi_Minh');
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => config('services.vnpay.tmn_code'),
            'vnp_Amount' => (string) round((float) $order->total_amount * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => (string) $order->id,
            'vnp_OrderInfo' => 'Thanh toan don hang '.$order->code,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => config('services.vnpay.return_url') ?: route('vnpay.return'),
            'vnp_IpAddr' => $request->ip() === '::1' ? '127.0.0.1' : $request->ip(),
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => $now->copy()->addMinutes(15)->format('YmdHis'),
        ];

        $params['vnp_SecureHash'] = $this->sign($params);

        return rtrim(config('services.vnpay.url'), '?').'?'.http_build_query($params);
    }

    public function verify(array $params): bool
    {
        $receivedHash = (string) ($params['vnp_SecureHash'] ?? '');
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        return $receivedHash !== '' && hash_equals($this->sign($params), $receivedHash);
    }

    private function sign(array $params): string
    {
        ksort($params);

        return hash_hmac('sha512', http_build_query($params), (string) config('services.vnpay.hash_secret'));
    }
}
