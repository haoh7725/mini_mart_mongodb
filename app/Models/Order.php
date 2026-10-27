<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user() { return $this->belongsTo(User::class); }

    // Đơn khách vãng lai chỉ được gắn theo email khi tài khoản đã xác minh email đó.
    public function scopeOwnedBy($query, User $user)
    {
        return $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->id);
            if ($user->hasVerifiedEmail()) {
                $query->orWhere(fn ($legacy) => $legacy->whereNull('user_id')->where('customer_email', $user->email));
            }
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id
            || ($this->user_id === null && $user->hasVerifiedEmail() && $this->customer_email === $user->email);
    }
}
