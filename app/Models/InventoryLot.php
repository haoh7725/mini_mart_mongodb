<?php

namespace App\Models;

use App\Models\MongoModel as Model;

class InventoryLot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rescueCampaign()
    {
        return $this->hasOne(RescueCampaign::class);
    }
}
