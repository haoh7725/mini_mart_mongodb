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

    public const CANCEL_REASONS = [
        'Tôi muốn đổi địa chỉ hoặc số điện thoại nhận hàng',
        'Tôi muốn thay đổi sản phẩm trong đơn',
        'Tôi tìm được chỗ mua khác rẻ hơn',
        'Tôi không còn nhu cầu mua nữa',
        'Lý do khác',
    ];

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    // Khách chỉ tự hủy được khi đơn chưa bắt đầu xử lý và chưa trả tiền (đã trả cần shop hoàn tiền).
    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true) && $this->payment_status !== 'paid';
    }

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
