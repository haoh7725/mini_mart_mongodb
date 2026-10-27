<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RescueCampaign extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function product() { return $this->belongsTo(Product::class); }
    public function inventoryLot() { return $this->belongsTo(InventoryLot::class); }

    public function isRunning(): bool
    {
        return $this->is_active && $this->remaining_quantity > 0
            && $this->starts_at <= now() && $this->ends_at >= now();
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->initial_quantity - (float) $this->rescued_quantity);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereColumn('rescued_quantity', '<', 'initial_quantity')
            ->where('starts_at', '<=', now())->where('ends_at', '>=', now());
    }
}
