<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) return null;

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : asset('storage/'.$this->image);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
