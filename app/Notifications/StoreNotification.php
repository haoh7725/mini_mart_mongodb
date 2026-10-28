<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

// Thông báo trong web (chuông): lưu vào bảng notifications, hiển thị cho khách, nhân viên và shipper.
class StoreNotification extends Notification
{
    public const KINDS = [
        'new_order' => '🛒',
        'order_update' => '🚚',
        'order_cancelled' => '✕',
        'return_request' => '↩',
        'delivery_failed' => '⚠',
    ];

    public function __construct(
        public string $kind,
        public string $title,
        public ?string $body,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
