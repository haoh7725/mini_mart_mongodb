<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique();
            $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::table('users', fn (Blueprint $table) => $table->foreignId('role_id')->nullable()->after('id')->constrained()->nullOnDelete());

        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name'); $table->string('phone', 20); $table->string('province');
            $table->string('district'); $table->string('ward'); $table->string('address_line');
            $table->boolean('is_default')->default(false); $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable();
            $table->string('image')->nullable(); $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('brands', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique();
            $table->string('logo')->nullable(); $table->text('description')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('units', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('symbol', 20)->unique(); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->string('name'); $table->string('slug')->unique(); $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique(); $table->text('short_description')->nullable();
            $table->longText('description')->nullable(); $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2); $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamp('sale_starts_at')->nullable(); $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('track_lot')->default(false); $table->boolean('track_expiry')->default(false);
            $table->boolean('is_featured')->default(false); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('product_images', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path'); $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0); $table->boolean('is_primary')->default(false); $table->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->json('options'); $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique(); $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable(); $table->decimal('sale_price', 15, 2)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->unique();
            $table->string('address')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('lot_number')->nullable(); $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable(); $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved_quantity', 15, 3)->default(0); $table->decimal('unit_cost', 15, 2)->default(0);
            $table->timestamps(); $table->index(['warehouse_id', 'product_id', 'product_variant_id']);
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->unique();
            $table->string('contact_name')->nullable(); $table->string('phone', 20)->nullable();
            $table->string('email')->nullable(); $table->string('tax_code')->nullable();
            $table->string('address')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft','ordered','partially_received','received','cancelled'])->default('draft');
            $table->date('ordered_at')->nullable(); $table->date('expected_at')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0); $table->text('note')->nullable(); $table->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 3); $table->decimal('received_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2); $table->decimal('line_total', 15, 2); $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['purchase','sale','return','adjustment','transfer_in','transfer_out']);
            $table->decimal('quantity', 15, 3); $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable(); $table->text('note')->nullable(); $table->timestamps();
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->enum('type', ['percentage','fixed']);
            $table->decimal('value', 15, 2); $table->decimal('minimum_order_amount', 15, 2)->default(0);
            $table->decimal('maximum_discount_amount', 15, 2)->nullable(); $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0); $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('session_id')->nullable()->unique(); $table->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete(); $table->string('customer_name');
            $table->string('customer_phone', 20); $table->string('customer_email')->nullable(); $table->text('shipping_address');
            $table->enum('status', ['pending','confirmed','processing','shipping','completed','cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid','paid','failed','refunded'])->default('unpaid');
            $table->decimal('subtotal', 15, 2); $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0); $table->decimal('total_amount', 15, 2);
            $table->text('customer_note')->nullable(); $table->text('cancel_reason')->nullable(); $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name'); $table->string('sku'); $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2); $table->decimal('line_total', 15, 2); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['cod','bank_transfer','online'])->default('cod');
            $table->string('transaction_id')->nullable()->unique(); $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending','completed','failed','refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating'); $table->string('title')->nullable(); $table->text('content')->nullable();
            $table->enum('status', ['pending','approved','rejected'])->default('pending'); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reviews','payments','order_items','orders','cart_items','carts','coupons','stock_movements',
            'purchase_order_items','purchase_orders','suppliers','inventory_lots','warehouses','product_variants',
            'product_images','products','units','brands','categories','addresses'] as $table) Schema::dropIfExists($table);
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('role_id'));
        Schema::dropIfExists('roles');
    }
};
