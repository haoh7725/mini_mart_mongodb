<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryLots()
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function rescueCampaigns() { return $this->hasMany(RescueCampaign::class); }
    public function reviews() { return $this->hasMany(Review::class); }

    public function activeRescueCampaign()
    {
        return $this->hasOne(RescueCampaign::class)->active()->orderBy('rescue_price');
    }

    public function getPrimaryImageAttribute(): ?string
    {
        return $this->images->firstWhere('is_primary', true)?->path ?? $this->images->first()?->path;
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->images->firstWhere('is_primary', true)?->url ?? $this->images->first()?->url;
    }

    public function getCurrentPriceAttribute(): float
    {
        $rescuePrice = $this->activeRescueCampaign?->rescue_price;
        if ($rescuePrice !== null) return (float) $rescuePrice;
        $saleIsActive = $this->sale_price !== null
            && ($this->sale_starts_at === null || $this->sale_starts_at->isPast())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());

        return (float) ($saleIsActive ? $this->sale_price : $this->selling_price);
    }

    public function getStockAttribute(): float
    {
        return (float) $this->inventoryLots->sum(fn ($lot) => $lot->quantity - $lot->reserved_quantity);
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'selling_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }
}
