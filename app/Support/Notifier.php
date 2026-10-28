<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\StoreNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class Notifier
{
    // Báo cho toàn bộ nhân viên đang hoạt động (trừ người vừa thao tác).
    // Gửi sau khi transaction commit để không giữ khóa dòng trong lúc ghi thông báo.
    public static function staff(Order $order, string $kind, string $title, ?string $body = null): void
    {
        $actorId = auth()->id();
        DB::afterCommit(function () use ($order, $kind, $title, $body, $actorId) {
            $staff = User::query()->activeWithRole(...User::STAFF_ROLES)->whereKeyNot($actorId ?? 0)->get();
            Notification::send($staff, new StoreNotification($kind, $title, $body, route('admin.orders.show', $order)));
        });
    }

    // Báo cho khách chủ đơn, bỏ qua khi chính khách là người thao tác hoặc đơn của khách vãng lai.
    public static function customer(Order $order, string $title, ?string $body = null): void
    {
        if (! $order->user_id || $order->user_id === auth()->id()) {
            return;
        }
        DB::afterCommit(fn () => $order->user?->notify(
            new StoreNotification('order_update', 'Đơn '.$order->code.': '.$title, $body, route('account.orders.show', $order->code))
        ));
    }

    // Dữ liệu cho chuông thông báo: dùng chung cho lần render trang và các lần gọi định kỳ.
    // Layout quản trị đã tự đếm đơn chờ xác nhận nên lần render trang không cần đếm lại.
    public static function feed(User $user, bool $withPendingOrders = true): array
    {
        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->take(10)->get()->map(fn ($notification) => self::present($notification)),
            'pending_orders' => $withPendingOrders && $user->isStaff() ? Order::query()->pending()->count() : null,
        ];
    }

    public static function present(DatabaseNotification $notification): array
    {
        $kind = $notification->data['kind'] ?? 'order_update';

        return [
            'id' => $notification->id,
            'kind' => $kind,
            'icon' => StoreNotification::KINDS[$kind] ?? '•',
            'title' => $notification->data['title'] ?? 'Thông báo',
            'body' => $notification->data['body'] ?? null,
            'url' => route('notifications.open', $notification->id),
            'time' => $notification->created_at->locale('vi')->diffForHumans(),
            'read' => $notification->read_at !== null,
        ];
    }
}
