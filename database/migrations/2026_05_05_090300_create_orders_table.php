<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->unsignedTinyInteger('status')->default(OrderStatus::Pending->value);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_country');
            $table->string('shipping_post_code');
            $table->text('shipping_full_address');
            $table->text('cancellation_reason')->nullable();
            $table->foreignUlid('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('cancelled_by_role')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('status')->references('id')->on('order_statuses')->restrictOnDelete();
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
