<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\RescueCampaign;
use Illuminate\Http\Request;

class RescueController extends Controller
{
    public function index(Request $request)
    {
        $days = min(30, max(1, $request->integer('days', 7)));
        $lots = InventoryLot::query()->with(['product.images', 'rescueCampaign'])
            ->whereNotNull('expires_at')->where('expires_at', '>=', today())
            ->where('expires_at', '<=', today()->addDays($days))
            ->whereRaw('quantity > reserved_quantity')->orderBy('expires_at')->paginate(15)->withQueryString();

        return view('admin.rescue.index', [
            'lots' => $lots, 'days' => $days,
            'stats' => [
                'active' => RescueCampaign::active()->count(),
                'rescued' => RescueCampaign::sum('rescued_quantity'),
                'saved' => RescueCampaign::selectRaw('SUM(rescued_quantity * (original_price - rescue_price)) as total')->value('total') ?? 0,
            ],
        ]);
    }

    public function store(Request $request, InventoryLot $lot)
    {
        abort_if(! $lot->expires_at || $lot->expires_at->lt(today()), 422, 'Lô hàng đã hết hạn.');
        $data = $request->validate(['discount_percent' => ['required', 'integer', 'min:5', 'max:80']]);
        $product = $lot->product;
        $originalPrice = (float) ($product->sale_price ?: $product->selling_price);
        $campaign = RescueCampaign::query()->firstOrNew(['inventory_lot_id' => $lot->id]);
        // Khi mở lại, giữ số đã giải cứu và cộng thêm phần tồn còn lại của lô.
        $campaign->fill([
            'product_id' => $product->id,
            'discount_percent' => $data['discount_percent'],
            'original_price' => $originalPrice,
            'rescue_price' => round($originalPrice * (100 - $data['discount_percent']) / 100, -3),
            'initial_quantity' => (float) $campaign->rescued_quantity + max(0, $lot->quantity - $lot->reserved_quantity),
            'is_active' => true, 'starts_at' => now(), 'ends_at' => $lot->expires_at->endOfDay(),
        ])->save();

        return back()->with('success', 'Đã mở chiến dịch giải cứu cho '.$product->name.'.');
    }

    public function stop(RescueCampaign $campaign)
    {
        $campaign->update(['is_active' => false]);
        return back()->with('success', 'Đã dừng chiến dịch giải cứu.');
    }
}
