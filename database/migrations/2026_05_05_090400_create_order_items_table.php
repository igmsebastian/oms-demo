<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('product_sku');
            $table->rawColumn('quantity', 'integer not null check (quantity > 0)');
            $table->rawColumn('cancelled_quantity', 'integer not null default 0 check (cancelled_quantity >= 0 and cancelled_quantity <= quantity)');
            $table->rawColumn('refunded_quantity', 'integer not null default 0 check (refunded_quantity >= 0 and refunded_quantity <= quantity)');
            $table->rawColumn('unit_price', 'decimal(12, 2) not null check (unit_price >= 0)');
            $table->rawColumn('line_total', 'decimal(12, 2) not null check (line_total >= 0)');
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
