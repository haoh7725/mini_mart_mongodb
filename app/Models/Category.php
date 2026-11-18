<?php

namespace App\Models;

use App\Models\MongoModel as Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : Storage::disk(config('filesystems.media'))->url($this->image);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
