<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VnpayController extends Controller
{
    public function return(Request $request, VnpayService $vnpay)
    {
        $result = $this->process($request, $vnpay);

        return view('store.payment-result', [
            'success' => $result['paid'] ?? false,
            'message' => $result['message'],
            'order' => $result['order'],
        ]);
    }

    public function ipn(Request $request, VnpayService $vnpay): JsonResponse
    {
        $result = $this->process($request, $vnpay);

        return response()->json(['RspCode' => $result['code'], 'Message' => $result['message']]);
    }

    private function process(Request $request, VnpayService $vnpay): array
    {
        $params = $request->query();
        if (! $vnpay->verify($params)) {
            return ['code' => '97', 'message' => 'Chữ ký VNPay không hợp lệ.', 'order' => null, 'paid' => false];
        }

        $order = Order::query()->find($params['vnp_TxnRef'] ?? null);
        if (! $order) {
            return ['code' => '01', 'message' => 'Không tìm thấy đơn hàng.', 'order' => null, 'paid' => false];
        }

        if ((int) ($params['vnp_Amount'] ?? 0) !== (int) round((float) $order->total_amount * 100)) {
            return ['code' => '04', 'message' => 'Số tiền thanh toán không khớp.', 'order' => $order, 'paid' => false];
        }

        $successful = ($params['vnp_ResponseCode'] ?? null) === '00'
            && ($params['vnp_TransactionStatus'] ?? null) === '00';

        DB::transaction(function () use ($order, $params, $successful) {
            $payment = DB::table('payments')->where('order_id', $order->id)->where('method', 'online')->lockForUpdate()->first();
            if (! $payment || $payment->status !== 'pending') {
                return;
            }

            // VNPay trả TransactionNo = 0 cho giao dịch hủy, không được ghi vào cột unique.
            $transactionNo = $params['vnp_TransactionNo'] ?? null;
            DB::table('payments')->where('id', $payment->id)->update([
                'status' => $successful ? 'completed' : 'failed',
                'transaction_id' => $successful && filled($transactionNo) && $transactionNo !== '0' ? $transactionNo : null,
                'paid_at' => $successful ? now() : null,
                'metadata' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
            $order->update(['payment_status' => $successful ? 'paid' : 'failed']);
            if (! $successful) {
                app(OrderService::class)->cancel($order);
            }
        });

        return [
            'code' => '00',
            'message' => $successful ? 'Thanh toán VNPay thành công.' : 'Giao dịch chưa thành công hoặc đã bị hủy.',
            'order' => $order->fresh(),
            'paid' => $successful,
        ];
    }
}
