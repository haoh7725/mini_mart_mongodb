<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin', 'manager', 'staff') ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'alpha_dash', 'max:200', Rule::unique('products')->ignore($product?->id)],
            'sku' => ['required', 'alpha_dash', 'max:80', Rule::unique('products')->ignore($product?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'gt:cost_price'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:selling_price'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'selling_price.gt' => 'Giá bán phải lớn hơn giá vốn.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá bán.',
            'images.max' => 'Mỗi lần chỉ được chọn tối đa 8 ảnh.',
            'images.*.image' => 'Tệp đã chọn phải là hình ảnh.',
            'images.*.max' => 'Mỗi ảnh sản phẩm không được vượt quá 2 MB.',
        ];
    }
}
