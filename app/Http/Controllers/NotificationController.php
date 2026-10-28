<?php

namespace App\Http\Controllers;

use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Chuông thông báo gọi định kỳ để cập nhật số chưa đọc, danh sách và số đơn chờ xác nhận.
    public function index(Request $request): JsonResponse
    {
        return response()->json(Notifier::feed($request->user()));
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('home'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->noContent() : back();
    }
}
