<?php

namespace App\Models;

use App\Models\MongoModel as Model;

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

    public const FREE_SHIPPING_THRESHOLD = 300000;

    public const SHIPPING_FEE = 25000;

    // Shipper của cửa hàng giao trong 2 giờ kể từ lúc shop xác nhận đơn.
    public const DELIVERY_HOURS = 2;

    // Khách được yêu cầu trả hàng/hoàn tiền trong số ngày này sau khi nhận hàng.
    public const RETURN_WINDOW_DAYS = 3;

    public const STATUS_LABELS = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang chuẩn bị hàng',
        'shipping' => 'Đang giao',
        'completed' => 'Đã giao',
        'cancelled' => 'Đã hủy',
        'returned' => 'Trả hàng/Hoàn tiền',
    ];

    public const PAYMENT_LABELS = ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thanh toán thất bại', 'refunded' => 'Đã hoàn tiền'];

    // Đơn đã được xác nhận và chưa giao xong: có giờ giao dự kiến và shipper phụ trách.
    public const IN_DELIVERY = ['confirmed', 'processing', 'shipping'];

    public const AWAITING_PICKUP = ['confirmed', 'processing'];

    // Đơn đã đóng, không cập nhật trạng thái được nữa.
    public const CLOSED = ['cancelled', 'returned'];

    // Các tab trong mục "Đơn hàng đã mua" của khách, giống cách Shopee nhóm trạng thái.
    public const CUSTOMER_TABS = [
        'all' => 'Tất cả',
        'pending' => 'Chờ xác nhận',
        'pickup' => 'Chờ lấy hàng',
        'shipping' => 'Chờ giao hàng',
        'completed' => 'Đã giao',
        'cancelled' => 'Đã hủy',
        'returns' => 'Trả hàng/Hoàn tiền',
    ];

    // Phí giao tính trên tiền hàng trước khi áp mã giảm giá.
    public static function shippingFeeFor(float $subtotal): int
    {
        return $subtotal >= self::FREE_SHIPPING_THRESHOLD ? 0 : self::SHIPPING_FEE;
    }

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime', 'estimated_delivery_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function trackingEvents()
    {
        return $this->hasMany(OrderTrackingEvent::class)->latest()->latest('id');
    }

    public function returnRequest()
    {
        return $this->hasOne(ReturnRequest::class);
    }

    public function latestTrackingEvent()
    {
        return $this->hasOne(OrderTrackingEvent::class)->latest('created_at')->latest('id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    // Đơn đang chờ shop duyệt trả hàng thì hiện trạng thái yêu cầu thay cho trạng thái đơn.
    public function displayStatusLabel(): string
    {
        return $this->returnRequest && $this->status !== 'returned'
            ? ReturnRequest::STATUS_LABELS[$this->returnRequest->status]
            : $this->statusLabel();
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_LABELS[$this->payment_status] ?? $this->payment_status;
    }

    public function isInDelivery(): bool
    {
        return in_array($this->status, self::IN_DELIVERY, true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED, true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForCustomerTab($query, string $tab)
    {
        return match ($tab) {
            'pending' => $query->where('status', 'pending'),
            'pickup' => $query->whereIn('status', self::AWAITING_PICKUP),
            'shipping' => $query->where('status', 'shipping'),
            'completed' => $query->where('status', 'completed')->whereDoesntHave('returnRequest'),
            'cancelled' => $query->where('status', 'cancelled'),
            'returns' => $query->where(fn ($q) => $q->where('status', 'returned')->orWhereHas('returnRequest')),
            default => $query,
        };
    }

    public function canRequestReturn(): bool
    {
        $deliveredAt = $this->delivered_at ?? $this->updated_at;

        return $this->status === 'completed' && ! $this->returnRequest
            && $deliveredAt && $deliveredAt->gte(now()->subDays(self::RETURN_WINDOW_DAYS));
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
