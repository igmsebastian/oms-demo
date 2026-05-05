<?php

use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductUnit;
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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUlid('product_category_id')->nullable()->after('description')->constrained()->nullOnDelete();
            $table->foreignUlid('product_brand_id')->nullable()->after('product_category_id')->constrained()->nullOnDelete();
            $table->foreignUlid('product_unit_id')->nullable()->after('product_brand_id')->constrained()->nullOnDelete();
            $table->foreignUlid('product_size_id')->nullable()->after('product_unit_id')->constrained()->nullOnDelete();
            $table->foreignUlid('product_color_id')->nullable()->after('product_size_id')->constrained()->nullOnDelete();

            $table->index('product_category_id');
            $table->index('product_brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_category_id']);
            $table->dropIndex(['product_brand_id']);

            $table->dropConstrainedForeignIdFor(ProductCategory::class);
            $table->dropConstrainedForeignIdFor(ProductBrand::class);
            $table->dropConstrainedForeignIdFor(ProductUnit::class);
            $table->dropConstrainedForeignIdFor(ProductSize::class);
            $table->dropConstrainedForeignIdFor(ProductColor::class);
        });
    }
};
