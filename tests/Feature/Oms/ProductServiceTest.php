<?php

use App\Enums\InventoryChangeType;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductTag;
use App\Models\ProductUnit;
use App\Models\User;
use App\Services\ProductService;
use App\Services\ReportService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('admin can create products with taxonomy and users cannot create products', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create();
    $brand = ProductBrand::factory()->create();
    $unit = ProductUnit::factory()->create();
    $size = ProductSize::factory()->create();
    $color = ProductColor::factory()->create();
    $tags = ProductTag::factory()->count(2)->create();

    $this->actingAs($admin)
        ->post(route('products.store'), [
            'sku' => 'SNEAK-TEST-001',
            'name' => 'Northcourt Baseline Classic',
            'description' => 'A realistic sneaker product.',
            'product_category_id' => $category->id,
            'product_brand_id' => $brand->id,
            'product_unit_id' => $unit->id,
            'product_size_id' => $size->id,
            'product_color_id' => $color->id,
            'tag_ids' => $tags->pluck('id')->all(),
            'price' => 145.50,
            'stock_quantity' => 12,
            'low_stock_threshold' => 3,
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::where('sku', 'SNEAK-TEST-001')->firstOrFail();

    expect($product->tags)->toHaveCount(2)
        ->and($product->is_active)->toBeTrue()
        ->and($product->category->is($category))->toBeTrue();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'sku' => 'SNEAK-DENIED',
            'name' => 'Denied Product',
            'price' => 99,
            'stock_quantity' => 2,
        ])
        ->assertForbidden();
});

test('product create and update validate required numeric and unique fields', function () {
    $admin = User::factory()->admin()->create();
    $product = createOmsProduct(['sku' => 'UNIQUE-SKU']);

    $this->actingAs($admin)
        ->post(route('products.store'), [
            'sku' => 'UNIQUE-SKU',
            'name' => '',
            'price' => -1,
            'stock_quantity' => -1,
        ])
        ->assertSessionHasErrors(['sku', 'name', 'price', 'stock_quantity']);

    $this->actingAs($admin)
        ->patch(route('products.update', $product), [
            'sku' => 'UNIQUE-SKU',
            'price' => 155.25,
            'stock_quantity' => 4,
        ])
        ->assertRedirect(route('products.index'));

    expect($product->fresh()->price)->toBe('155.25')
        ->and($product->fresh()->stock_quantity)->toBe(4);
});

test('product updates sync tags invalidate caches and preserve inventory logs', function () {
    $product = createOmsProduct(['stock_quantity' => 8]);
    $tags = ProductTag::factory()->count(2)->create();
    InventoryLog::create([
        'product_id' => $product->id,
        'change_type' => InventoryChangeType::Adjustment,
        'quantity_change' => 1,
        'stock_before' => 7,
        'stock_after' => 8,
        'reason' => 'Initial count',
    ]);

    app(ReportService::class)->inventoryStatus();
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeTrue();

    $updated = app(ProductService::class)->update($product, [
        'stock_quantity' => 3,
        'tag_ids' => [$tags[0]->id],
    ]);

    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse()
        ->and($updated->tags)->toHaveCount(1)
        ->and($updated->relationLoaded('tags'))->toBeTrue()
        ->and($product->inventoryLogs()->count())->toBe(1);

    $updated = app(ProductService::class)->update($updated, ['tag_ids' => [$tags[1]->id]]);

    expect($updated->tags->pluck('id')->all())->toBe([$tags[1]->id]);
});

test('product delete is soft and historical order item snapshots remain readable', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['name' => 'Historical Sneaker', 'sku' => 'HIST-001']);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 1]]);

    $this->actingAs($admin)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect(Product::withTrashed()->find($product->id)?->trashed())->toBeTrue()
        ->and($order->items()->first()->product_name)->toBe('Historical Sneaker')
        ->and($order->items()->first()->product_sku)->toBe('HIST-001');
});

test('product stock metrics distinguish in stock low stock and no stock products', function () {
    createOmsProduct(['stock_quantity' => 10, 'low_stock_threshold' => 3]);
    createOmsProduct(['stock_quantity' => 2, 'low_stock_threshold' => 3]);
    createOmsProduct(['stock_quantity' => 0, 'low_stock_threshold' => 3]);

    expect(app(ReportService::class)->productMetrics())->toMatchArray([
        'total_products' => 3,
        'in_stock_products' => 1,
        'low_stock_products' => 1,
        'no_stock_products' => 1,
    ]);
});
