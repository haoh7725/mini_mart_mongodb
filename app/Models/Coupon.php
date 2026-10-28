<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Coupon extends Model
{
    protected $guarded = ['id', 'used_count'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function discount(float $subtotal): float
    {
        if (! $this->is_active || ($this->starts_at && now()->lt($this->starts_at))
            || ($this->expires_at && now()->gt($this->expires_at))
            || ($this->usage_limit !== null && $this->used_count >= $this->usage_limit)) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá hết hạn, hết lượt hoặc chưa được kích hoạt.']);
        }
        if ($subtotal < $this->minimum_order_amount) {
            throw ValidationException::withMessages(['coupon_code' => 'Đơn hàng tối thiểu '.number_format($this->minimum_order_amount).'đ để dùng mã này.']);
        }
        $discount = $this->type === 'percentage' ? $subtotal * $this->value / 100 : (float) $this->value;
        if ($this->maximum_discount_amount !== null) {
            $discount = min($discount, (float) $this->maximum_discount_amount);
        }
        return round(max(0, min($subtotal, $discount)), 2);
    }

    public static function forCode(string $code, bool $lock = false): self
    {
        $query = static::where('code', strtoupper(trim($code)));
        $coupon = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $coupon) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không tồn tại.']);
        }
        return $coupon;
    }
}
