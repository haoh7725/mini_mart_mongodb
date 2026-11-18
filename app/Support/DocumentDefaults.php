<?php

namespace App\Support;

class DocumentDefaults
{
    public static function numericFields(string $collection): array
    {
        return match ($collection) {
            'products' => ['cost_price', 'selling_price', 'sale_price'],
            'inventory_lots' => ['quantity', 'reserved_quantity', 'unit_cost'],
            'coupons' => ['value', 'minimum_order_amount', 'maximum_discount_amount', 'usage_limit', 'used_count'],
            'orders' => ['subtotal', 'discount_amount', 'shipping_fee', 'total_amount'],
            'order_items' => ['quantity', 'unit_price', 'line_total'],
            'rescue_campaigns' => ['discount_percent', 'original_price', 'rescue_price', 'initial_quantity', 'rescued_quantity'],
            'categories' => ['sort_order'], 'product_images' => ['sort_order'],
            'reviews' => ['rating'], 'return_requests' => ['refund_amount'],
            'green_point_transactions' => ['points'],
            default => [],
        };
    }

    public static function for(string $collection): array
    {
        return match ($collection) {
            'users' => ['is_active' => true],
            'categories' => ['sort_order' => 0, 'is_active' => true],
            'brands', 'warehouses' => ['is_active' => true],
            'products' => ['cost_price' => 0, 'track_lot' => false, 'track_expiry' => false, 'is_featured' => false, 'is_active' => true],
            'product_images' => ['sort_order' => 0, 'is_primary' => false],
            'inventory_lots' => ['quantity' => 0, 'reserved_quantity' => 0, 'unit_cost' => 0],
            'coupons' => ['minimum_order_amount' => 0, 'used_count' => 0, 'is_active' => true],
            'orders' => ['status' => 'pending', 'payment_status' => 'unpaid', 'discount_amount' => 0, 'shipping_fee' => 0],
            'payments' => ['method' => 'cod', 'status' => 'pending'],
            'reviews' => ['status' => 'pending'],
            'return_requests' => ['status' => 'pending'],
            'rescue_campaigns' => ['rescued_quantity' => 0, 'is_active' => true],
            default => [],
        };
    }
}
