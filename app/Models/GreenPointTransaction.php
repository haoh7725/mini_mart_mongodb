<?php

namespace App\Models;

use App\Models\MongoModel as Model;

class GreenPointTransaction extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
