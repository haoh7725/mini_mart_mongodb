<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled'];

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipper_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('estimated_delivery_at')->nullable()->after('cancelled_at');
            $table->timestamp('delivered_at')->nullable()->after('estimated_delivery_at');
            $table->index('status');
            $table->index(['shipper_id', 'status']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [...self::STATUSES, 'returned'])->default('pending')->change();
        });

        // Hành trình đơn hàng: mỗi mốc do hệ thống, shop hoặc shipper ghi lại.
        Schema::create('order_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');
            $table->text('detail')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        DB::table('roles')->updateOrInsert(['slug' => 'shipper'], [
            'name' => 'Shipper',
            'description' => 'Nhận và giao đơn hàng của cửa hàng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('order_tracking_events');
        DB::table('orders')->where('status', 'returned')->update(['status' => 'completed']);
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', self::STATUSES)->default('pending')->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            // MySQL dùng index (shipper_id, status) cho khóa ngoại nên phải gỡ khóa ngoại trước.
            $table->dropForeign(['shipper_id']);
            $table->dropIndex(['shipper_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn(['shipper_id', 'estimated_delivery_at', 'delivered_at']);
        });
        $shipperRole = DB::table('roles')->where('slug', 'shipper')->value('id');
        DB::table('users')->where('role_id', $shipperRole)->update(['role_id' => null]);
        DB::table('roles')->where('slug', 'shipper')->delete();
    }
};
