<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, OrderItem $item)
    {
        abort_unless($item->order?->isOwnedBy($request->user()) && $item->order->status === 'completed', 403);
        abort_if(! $item->product_id, 422, 'Sản phẩm này không còn tồn tại.');
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        Review::query()->updateOrCreate(['order_item_id' => $item->id, 'user_id' => $request->user()->id], $data + [
            'product_id' => $item->product_id, 'status' => 'pending',
        ]);

        return back()->with('success', 'Đã gửi đánh giá. Nội dung sẽ hiển thị sau khi được duyệt.');
    }
}
