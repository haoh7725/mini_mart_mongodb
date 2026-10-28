<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('address_detail') && $this->filled('province_name') && $this->filled('ward_name')) {
            $this->merge([
                'shipping_address' => collect([
                    $this->string('address_detail')->trim()->toString(),
                    $this->string('ward_name')->trim()->toString(),
                    $this->string('province_name')->trim()->toString(),
                ])->filter()->implode(', '),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'regex:/^[0-9+(). -]{8,20}$/'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'shipping_address' => ['required', 'string', 'min:10', 'max:500'],
            'address_detail' => ['nullable', 'string', 'max:250'],
            'province_name' => ['nullable', 'string', 'max:100'],
            'ward_name' => ['nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'payment' => ['required', 'in:cod,vnpay'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng nhập họ tên người nhận.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'customer_phone.regex' => 'Số điện thoại không đúng định dạng.',
            'customer_email.email' => 'Email không đúng định dạng.',
            'shipping_address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'shipping_address.min' => 'Địa chỉ giao hàng cần ít nhất :min ký tự.',
        ];
    }
}
