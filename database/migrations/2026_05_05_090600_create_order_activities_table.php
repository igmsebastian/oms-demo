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
        Schema::create('order_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('actor_role')->nullable();
            $table->string('event');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('from_status')->nullable();
            $table->unsignedTinyInteger('to_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('from_status')->references('id')->on('order_statuses')->nullOnDelete();
            $table->foreign('to_status')->references('id')->on('order_statuses')->nullOnDelete();
            $table->index('order_id');
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_activities');
    }
};
