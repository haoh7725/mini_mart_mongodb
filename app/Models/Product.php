<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'selling_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryLots()
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function getCurrentPriceAttribute(): float
    {
        return (float) ($this->sale_price ?: $this->selling_price);
    }

    public function getStockAttribute(): float
    {
        return (float) $this->inventoryLots->sum(fn ($lot) => $lot->quantity - $lot->reserved_quantity);
    }
}
