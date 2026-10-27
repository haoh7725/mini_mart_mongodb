<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['user', 'product'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->paginate(15)->withQueryString();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function update(Request $request, Review $review)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        $review->update($data);
        return back()->with('success', $data['status'] === 'approved' ? 'Đã duyệt đánh giá.' : 'Đã ẩn đánh giá.');
    }
}
