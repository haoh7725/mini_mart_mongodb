<?php

namespace App\Http\Controllers;

use App\Models\RescueCampaign;
use App\Support\MongoQueries;

class RescueStoreController extends Controller
{
    public function index()
    {
        $campaigns = RescueCampaign::active()->with(['product.category', 'product.images', 'product.inventoryLots', 'inventoryLot'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))->latest()->get()->unique('product_id');
        $impact = [
            'rescued' => RescueCampaign::sum('rescued_quantity'),
            'saved' => MongoQueries::rescuedSavings(),
        ];

        return view('store.rescue', compact('campaigns', 'impact'));
    }
}
