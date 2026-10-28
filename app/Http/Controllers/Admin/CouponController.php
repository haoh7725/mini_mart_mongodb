<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons.index', ['coupons' => Coupon::latest()->paginate(20)]);
    }

    public function create()
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['is_active' => true])]);
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function store(Request $request)
    {
        Coupon::create($this->validated($request));
        return redirect()->route('admin.coupons.index')->with('success', 'Đã tạo mã giảm giá.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));
        return redirect()->route('admin.coupons.index')->with('success', 'Đã cập nhật mã giảm giá.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons')->ignore($coupon?->id)],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01', 'max:'.($request->input('type') === 'percentage' ? '100' : '9999999999999')],
            'minimum_order_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), 'after:starts_at')],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
