<?php

namespace App\Models;

use App\Models\MongoModel as Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
