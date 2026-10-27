<?php

namespace App\Services;

use App\Models\GreenPointTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public const POINTS_PER_RESCUED_UNIT = 10;
    private const POINT_REASON = 'Mua sản phẩm SmartSave';

    // Hủy đơn: hoàn kho từng lô, trả lại số lượng cho chiến dịch giải cứu và thu hồi điểm xanh.
    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->find($order->id);
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

            $locked->update(['status' => 'cancelled']);
            $order->setRawAttributes($locked->getAttributes(), true);
            $this->syncGreenPoints($order);
        });
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
