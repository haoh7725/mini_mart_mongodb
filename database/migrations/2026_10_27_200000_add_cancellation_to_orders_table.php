<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cột cancel_reason đã có từ migration gốc, chỉ bổ sung thời điểm hủy.
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'cancelled_at')) {
            Schema::table('orders', fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable()->after('cancel_reason'));
        }
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('cancelled_at'));
    }
};
