<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescue_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_lot_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_percent');
            $table->decimal('original_price', 15, 2);
            $table->decimal('rescue_price', 15, 2);
            $table->decimal('initial_quantity', 15, 3);
            $table->decimal('rescued_quantity', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rescue_campaigns');
    }
};
