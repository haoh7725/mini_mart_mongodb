<?php

namespace App\Models;

use App\Models\MongoModel as Model;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Compute the active regular/sale price inside MongoDB, including expiry dates.
    public static function effectivePriceExpression(): array
    {
        $now = new UTCDateTime(now());

        return ['$cond' => [
            ['$and' => [
                ['$ne' => [['$ifNull' => ['$sale_price', null]], null]],
                ['$lte' => [['$ifNull' => ['$sale_starts_at', $now]], $now]],
                ['$gte' => [['$ifNull' => ['$sale_ends_at', $now]], $now]],
            ]],
            '$sale_price', '$selling_price',
        ]];
    }

    public function inventoryLots()
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function rescueCampaigns()
    {
        return $this->hasMany(RescueCampaign::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

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

        return $rescuePrice !== null ? (float) $rescuePrice : $this->regular_price;
    }

    public function getRegularPriceAttribute(): float
    {
        $saleIsActive = $this->sale_price !== null
            && ($this->sale_starts_at === null || $this->sale_starts_at->isPast())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());

        return (float) ($saleIsActive ? $this->sale_price : $this->selling_price);
    }

    // Chia số lượng mua theo lô: lô đang giải cứu trước, sau đó lô gần hết hạn.
    // Chỉ phần còn lại của chiến dịch mới hưởng giá giải cứu. Cần nạp inventoryLots.rescueCampaign.
    public function allocate(int $quantity): array
    {
        $lots = $this->inventoryLots->sortBy(fn ($lot) => sprintf('%d-%020d',
            $lot->rescueCampaign?->isRunning() ? 0 : 1, $lot->expires_at?->timestamp ?? PHP_INT_MAX));
        $parts = [];
        $remaining = $quantity;
        foreach ($lots as $lot) {
            $take = min((float) $lot->quantity - (float) $lot->reserved_quantity, $remaining);
            if ($take <= 0) {
                continue;
            }
            $campaign = $lot->rescueCampaign?->isRunning() ? $lot->rescueCampaign : null;
            $rescued = $campaign ? min($take, $campaign->remaining_quantity) : 0;
            if ($rescued > 0) {
                $parts[] = ['lot' => $lot, 'campaign' => $campaign, 'quantity' => $rescued, 'unit_price' => (float) $campaign->rescue_price];
            }
            if ($take > $rescued) {
                $parts[] = ['lot' => $lot, 'campaign' => null, 'quantity' => $take - $rescued, 'unit_price' => $this->regular_price];
            }
            $remaining -= $take;
            if ($remaining <= 0) {
                break;
            }
        }

        return $parts;
    }

    public function priceFor(int $quantity): float
    {
        $parts = collect($this->allocate($quantity));
        $unallocated = max(0, $quantity - $parts->sum('quantity'));

        return (float) $parts->sum(fn ($part) => $part['quantity'] * $part['unit_price']) + $unallocated * $this->regular_price;
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
