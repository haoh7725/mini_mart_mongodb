<?php

namespace App\Models;

use App\Models\MongoModel as Model;

class ReturnRequest extends Model
{
    public const REASONS = [
        'Sản phẩm bị hư hỏng, dập nát',
        'Sản phẩm hết hạn hoặc kém chất lượng',
        'Giao sai hoặc thiếu sản phẩm',
        'Sản phẩm khác với mô tả',
        'Lý do khác',
    ];

    public const STATUS_LABELS = ['pending' => 'Chờ shop duyệt', 'approved' => 'Đã chấp nhận, hoàn tiền', 'rejected' => 'Bị từ chối'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
