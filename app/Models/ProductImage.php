<?php

namespace App\Models;

use App\Models\MongoModel as Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $guarded = [];

    public function getUrlAttribute(): string
    {
        return Str::startsWith($this->path, ['http://', 'https://'])
            ? $this->path
            : Storage::disk(config('filesystems.media'))->url($this->path);
    }
}
