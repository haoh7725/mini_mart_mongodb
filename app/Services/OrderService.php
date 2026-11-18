<?php

namespace App\Services;

use App\Models\GreenPointTransaction;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Support\DocumentTransaction;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public const POINTS_PER_RESCUED_UNIT = 10;

    private const POINT_REASON = 'Mua sản phẩm SmartSave';

    // Mốc hành trình tự ghi khi đơn chuyển trạng thái.
    private const STATUS_EVENTS = [
        'confirmed' => ['Shop đã xác nhận đơn hàng', 'Shipper của MiniMart sẽ giao trong khoảng '.Order::DELIVERY_HOURS.' giờ.'],
        'processing' => ['Shop đang chuẩn bị hàng', 'Hàng đang được nhặt và đóng gói tại cửa hàng.'],
        'shipping' => ['Đơn hàng đang được giao', 'Shipper đã nhận hàng và đang trên đường giao tới bạn.'],
        'completed' => ['Giao hàng thành công', 'Cảm ơn bạn đã mua sắm tại MiniMart.'],
    ];

    public function track(Order $order, string $title, ?string $description = null): void
    {
        $order->trackingEvents()->create(['title' => $title, 'description' => $description, 'created_by' => auth()->id()]);
        Notifier::customer($order, $title, $description);
    }

    // Đơn vừa tạo. Đơn COD báo ngay cho shop; đơn VNPay chỉ báo khi đã thanh toán (paid()).
    public function placed(Order $order, string $paymentMethod): void
    {
        $online = $paymentMethod === 'vnpay';
        $this->track($order, 'Đặt hàng thành công', $online ? 'Đơn hàng đang chờ thanh toán qua VNPay.' : 'Đơn hàng đang chờ shop xác nhận.');
        if (! $online) {
            $this->notifyNewOrder($order, 'COD');
        }
    }

    public function paid(Order $order): void
    {
        $this->track($order, 'Đã thanh toán qua VNPay', 'Đơn hàng đang chờ shop xác nhận.');
        $this->notifyNewOrder($order, 'Đã thanh toán VNPay');
    }

    public function deliveryFailed(Order $order, string $reason): void
    {
        $this->track($order, 'Giao hàng chưa thành công', $reason.'. Shipper sẽ liên hệ để giao lại.');
        Notifier::staff($order, 'delivery_failed', 'Giao chưa thành công '.$order->code, auth()->user()?->name.': '.$reason);
    }

    private function notifyNewOrder(Order $order, string $paymentLabel): void
    {
        Notifier::staff($order, 'new_order', 'Đơn mới '.$order->code,
            $order->customer_name.' · '.number_format($order->total_amount, 0, ',', '.').'đ · '.$paymentLabel);
    }

    // Đổi trạng thái đơn và ghi mốc hành trình tương ứng. Hủy đơn đi qua cancel() để hoàn kho.
    public function changeStatus(Order $order, string $status): void
    {
        if ($status === 'cancelled') {
            $this->cancel($order);

            return;
        }
        if ($status === $order->status) {
            return;
        }

        $changes = ['status' => $status];
        if (in_array($status, Order::IN_DELIVERY, true) && ! $order->estimated_delivery_at) {
            $changes['estimated_delivery_at'] = now()->addHours(Order::DELIVERY_HOURS);
        }
        if ($status === 'completed') {
            $changes['delivered_at'] = now();
            // Đơn COD: shipper thu tiền khi giao, nên giao xong là đã thanh toán.
            $collected = DB::table('payments')->where('order_id', $order->id)->where('method', 'cod')->where('status', 'pending')
                ->update(['status' => 'completed', 'paid_at' => now(), 'updated_at' => now()]);
            if ($collected) {
                $changes['payment_status'] = 'paid';
            }
        }
        $order->update($changes);

        [$title, $description] = self::STATUS_EVENTS[$status] ?? [$order->statusLabel(), null];
        $this->track($order, $title, $description);
        $this->syncGreenPoints($order);
    }

    public function requestReturn(Order $order, string $reason, ?string $detail = null): void
    {
        $order->returnRequest()->create(['user_id' => auth()->id(), 'reason' => $reason, 'detail' => $detail]);
        $summary = $reason.($detail ? ': '.$detail : '');
        $this->track($order, 'Khách gửi yêu cầu trả hàng/hoàn tiền', $summary);
        Notifier::staff($order, 'return_request', 'Yêu cầu trả hàng '.$order->code, $summary);
    }

    // Duyệt yêu cầu trả hàng. Hàng tươi sống trả về không tự nhập lại kho; shop tự kiểm tra rồi điều chỉnh.
    public function resolveReturn(Order $order, bool $approve, ?string $note = null): bool
    {
        return DocumentTransaction::run(function () use ($order, $approve, $note) {
            $request = ReturnRequest::query()->where('order_id', $order->id)->first();
            if (! $request || $request->status !== 'pending') {
                return false;
            }

            if (! $approve) {
                $request->update(['status' => 'rejected', 'admin_note' => $note, 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
                $this->track($order, 'Yêu cầu trả hàng bị từ chối', $note);

                return true;
            }

            $paid = $order->payment_status === 'paid';
            $refund = $paid ? (float) $order->total_amount : 0;
            $request->update(['status' => 'approved', 'admin_note' => $note, 'refund_amount' => $refund, 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
            $order->update(['status' => 'returned', 'payment_status' => $paid ? 'refunded' : $order->payment_status]);
            if ($paid) {
                DB::table('payments')->where('order_id', $order->id)->where('status', 'completed')
                    ->update(['status' => 'refunded', 'updated_at' => now()]);
            }
            $refundNote = $paid ? 'Số tiền hoàn: '.number_format($refund, 0, ',', '.').'đ.' : null;
            $this->track($order, 'Shop đã chấp nhận trả hàng/hoàn tiền', implode(' ', array_filter([$refundNote, $note])) ?: null);
            $this->syncGreenPoints($order);

            return true;
        });
    }

    // Khách tự hủy: chỉ khi đơn chưa xử lý và chưa trả tiền. Thanh toán online còn treo được đóng lại
    // để callback VNPay đến muộn không ghi nhận nữa.
    public function cancelByCustomer(Order $order, string $reason): bool
    {
        return DocumentTransaction::run(function () use ($order, $reason) {
            // Khóa đơn để shop không chuyển sang "đang giao" cùng lúc khách bấm hủy.
            $locked = Order::query()->findOrFail($order->id);
            if (! $locked->canBeCancelledByCustomer()) {
                return false;
            }
            $this->cancel($locked, $reason);
            DB::table('payments')->where('order_id', $locked->id)->where('status', 'pending')
                ->update(['status' => 'failed', 'updated_at' => now()]);
            Notifier::staff($locked, 'order_cancelled', 'Khách hủy đơn '.$locked->code, $reason);

            return true;
        });
    }

    // Hủy đơn: hoàn kho từng lô, trả lại số lượng cho chiến dịch giải cứu và thu hồi điểm xanh.
    public function cancel(Order $order, ?string $reason = null): void
    {
        DocumentTransaction::run(function () use ($order, $reason) {
            $locked = Order::query()->find($order->id);
            if (! $locked || $locked->status === 'cancelled') {
                return;
            }

            $sales = DB::table('stock_movements')->where('reference_type', Order::class)
                ->where('reference_id', $order->id)->where('type', 'sale')->get();
            foreach ($sales as $sale) {
                $quantity = abs((float) $sale->quantity);
                if ($sale->inventory_lot_id) {
                    DB::table('inventory_lots')->where('id', $sale->inventory_lot_id)->increment('quantity', $quantity);
                }
                if ($sale->rescue_campaign_id) {
                    DB::table('rescue_campaigns')->where('id', $sale->rescue_campaign_id)->decrement('rescued_quantity', $quantity);
                }
                DB::table('stock_movements')->insert([
                    'warehouse_id' => $sale->warehouse_id, 'product_id' => $sale->product_id,
                    'product_variant_id' => $sale->product_variant_id, 'inventory_lot_id' => $sale->inventory_lot_id,
                    'rescue_campaign_id' => $sale->rescue_campaign_id, 'created_by' => auth()->id(),
                    'type' => 'return', 'quantity' => $quantity,
                    'reference_type' => Order::class, 'reference_id' => $order->id,
                    'note' => 'Hoàn kho do hủy đơn '.$locked->code, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $locked->update(['status' => 'cancelled'] + ($reason ? ['cancelled_at' => now(), 'cancel_reason' => $reason] : []));
            $this->track($locked, 'Đơn hàng đã bị hủy', 'Sản phẩm đã được hoàn lại kho.');
            if ($locked->coupon_id) {
                DB::table('coupons')->where('id', $locked->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            }
            $order->setRawAttributes($locked->getAttributes(), true);
            $this->syncGreenPoints($order);
        });
    }

    // Đơn VNPay bị bỏ dở (khách đóng tab, không có IPN) vẫn giữ hàng trong kho: quá hạn thì hủy và hoàn kho.
    // Liên kết VNPay hết hạn sau 15 phút, chờ thêm để IPN đến muộn của giao dịch hợp lệ vẫn kịp ghi nhận.
    public function expireUnpaidOnlinePayments(int $minutes = 30): int
    {
        $orderIds = DB::table('payments')->where('method', 'online')->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))->pluck('order_id');

        return $orderIds->filter(fn ($orderId) => DocumentTransaction::run(function () use ($orderId) {
            // Cùng thứ tự khóa với VnpayController để IPN đến cùng lúc không ghi nhận đơn đã hủy.
            $payment = DB::table('payments')->where('order_id', $orderId)->where('method', 'online')->first();
            if (! $payment || $payment->status !== 'pending') {
                return false;
            }
            DB::table('payments')->where('id', $payment->id)->update(['status' => 'failed', 'updated_at' => now()]);
            $order = Order::query()->findOrFail($orderId);
            $order->update(['payment_status' => 'failed']);
            $this->cancel($order, 'Quá hạn thanh toán VNPay');

            return true;
        }))->count();
    }

    // Điểm xanh chỉ tồn tại khi đơn đã hoàn thành.
    public function syncGreenPoints(Order $order): void
    {
        $award = GreenPointTransaction::query()->where('order_id', $order->id)->where('reason', self::POINT_REASON);
        if ($order->status !== 'completed' || ! $order->user_id) {
            $award->delete();

            return;
        }

        $rescued = abs((float) DB::table('stock_movements')->where('reference_type', Order::class)
            ->where('reference_id', $order->id)->where('type', 'sale')
            ->whereNotNull('rescue_campaign_id')->sum('quantity'));
        $points = (int) round($rescued * self::POINTS_PER_RESCUED_UNIT);
        if ($points > 0) {
            GreenPointTransaction::query()->firstOrCreate(
                ['user_id' => $order->user_id, 'order_id' => $order->id, 'reason' => self::POINT_REASON],
                ['points' => $points],
            );
        }
    }
}
