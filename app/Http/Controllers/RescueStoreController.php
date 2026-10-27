<?php

namespace App\Http\Controllers;

use App\Models\RescueCampaign;

class RescueStoreController extends Controller
{
    public function index()
    {
        $campaigns = RescueCampaign::active()->with(['product.category', 'product.images', 'product.inventoryLots', 'inventoryLot'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))->latest()->get()->unique('product_id');
        $impact = [
            'rescued' => RescueCampaign::sum('rescued_quantity'),
            'saved' => RescueCampaign::selectRaw('SUM(rescued_quantity * (original_price - rescue_price)) as total')->value('total') ?? 0,
        ];

        return view('store.rescue', compact('campaigns', 'impact'));
    }
}
