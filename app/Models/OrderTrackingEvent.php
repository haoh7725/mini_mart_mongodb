<?php

namespace App\Models;

use App\Models\MongoModel as Model;

class OrderTrackingEvent extends Model
{
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
