<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLLECTIONS = [
        'roles', 'users', 'addresses', 'categories', 'brands', 'units', 'products',
        'product_images', 'product_variants', 'warehouses', 'inventory_lots',
        'suppliers', 'purchase_orders', 'purchase_order_items', 'stock_movements',
        'coupons', 'carts', 'cart_items', 'orders', 'order_items', 'payments',
        'reviews', 'rescue_campaigns', 'green_point_transactions',
        'order_tracking_events', 'return_requests', 'notifications', 'password_reset_tokens',
    ];

    public function up(): void
    {
        $database = DB::connection('mongodb')->getDatabase();
        $existing = iterator_to_array($database->listCollectionNames());
        foreach (self::COLLECTIONS as $name) {
            if (! in_array($name, $existing, true)) {
                $database->createCollection($name);
            }
        }

        foreach ([
            'roles' => ['slug'], 'users' => ['email'], 'categories' => ['slug'],
            'brands' => ['slug'], 'units' => ['symbol'], 'products' => ['slug', 'sku'],
            'product_variants' => ['sku'], 'warehouses' => ['code'], 'suppliers' => ['code'],
            'purchase_orders' => ['code'], 'coupons' => ['code'], 'orders' => ['code'],
            'return_requests' => ['order_id'], 'rescue_campaigns' => ['inventory_lot_id'],
            'password_reset_tokens' => ['email'],
        ] as $collection => $fields) {
            foreach ($fields as $field) {
                $database->selectCollection($collection)->createIndex([$field => 1], ['unique' => true]);
            }
        }

        // MongoDB unique indexes otherwise treat all missing/null values as duplicates.
        foreach (['users' => ['phone', 'google_id'], 'products' => ['barcode'], 'product_variants' => ['barcode'], 'payments' => ['transaction_id'], 'carts' => ['session_id']] as $collection => $fields) {
            foreach ($fields as $field) {
                $database->selectCollection($collection)->createIndex([$field => 1], [
                    'unique' => true, 'partialFilterExpression' => [$field => ['$type' => 'string']],
                ]);
            }
        }

        foreach ([
            'users' => [['role_id' => 1]],
            'products' => [['is_active' => 1, 'category_id' => 1], ['brand_id' => 1], ['is_active' => 1, 'selling_price' => 1]],
            'product_images' => [['product_id' => 1, 'sort_order' => 1]],
            'inventory_lots' => [['warehouse_id' => 1, 'product_id' => 1, 'product_variant_id' => 1], ['product_id' => 1], ['expires_at' => 1]],
            'orders' => [['status' => 1, 'created_at' => 1], ['user_id' => 1, 'created_at' => -1], ['shipper_id' => 1, 'status' => 1]],
            'order_items' => [['order_id' => 1], ['product_id' => 1]],
            'payments' => [['order_id' => 1, 'method' => 1], ['method' => 1, 'status' => 1, 'created_at' => 1]],
            'stock_movements' => [['reference_type' => 1, 'reference_id' => 1, 'type' => 1]],
            'reviews' => [['product_id' => 1, 'status' => 1], ['order_item_id' => 1]],
            'order_tracking_events' => [['order_id' => 1, 'created_at' => -1]],
            'green_point_transactions' => [['user_id' => 1], ['order_id' => 1, 'reason' => 1]],
            'notifications' => [['notifiable_type' => 1, 'notifiable_id' => 1, 'created_at' => -1]],
            'addresses' => [['user_id' => 1]],
        ] as $collection => $indexes) {
            foreach ($indexes as $index) {
                $database->selectCollection($collection)->createIndex($index);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::COLLECTIONS) as $collection) {
            DB::connection('mongodb')->getDatabase()->dropCollection($collection);
        }
    }
};
