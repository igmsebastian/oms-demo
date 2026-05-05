<?php

use App\Filters\ProductFilter;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

function paginatedProducts(array $query): LengthAwarePaginator
{
    return app(ProductRepository::class)->paginate(new ProductFilter(Request::create('/products', 'GET', $query)));
}

test('product repository paginates and eager loads reference data', function () {
    $category = ProductCategory::factory()->create();
    $brand = ProductBrand::factory()->create();
    $tag = ProductTag::factory()->create();
    $product = createOmsProduct([
        'product_category_id' => $category->id,
        'product_brand_id' => $brand->id,
    ]);
    $product->tags()->sync([$tag->id]);

    $paginator = paginatedProducts(['per_page' => 1]);
    $row = $paginator->items()[0];

    expect($paginator->perPage())->toBe(1)
        ->and($row->relationLoaded('category'))->toBeTrue()
        ->and($row->relationLoaded('brand'))->toBeTrue()
        ->and($row->relationLoaded('tags'))->toBeTrue();
});

test('product filters search keyword taxonomy tags active and stock status across the full query', function () {
    $category = ProductCategory::factory()->create();
    $brand = ProductBrand::factory()->create();
    $tag = ProductTag::factory()->create();
    $match = createOmsProduct([
        'sku' => 'RUN-MATCH-001',
        'name' => 'Apex Runner Match',
        'product_category_id' => $category->id,
        'product_brand_id' => $brand->id,
        'stock_quantity' => 2,
        'low_stock_threshold' => 5,
        'is_active' => true,
    ]);
    $match->tags()->sync([$tag->id]);

    createOmsProduct(['sku' => 'OTHER-001', 'name' => 'Other Product', 'stock_quantity' => 15]);
    createOmsProduct(['sku' => 'INACTIVE-001', 'name' => 'Inactive Product', 'is_active' => false]);

    $paginator = paginatedProducts([
        'per_page' => 1,
        'filters' => [
            'keyword' => 'RUN-MATCH',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'tag_ids' => [$tag->id],
            'is_active' => true,
            'stock_status' => 'low_stock',
        ],
    ]);

    expect($paginator->total())->toBe(1)
        ->and($paginator->items()[0]->id)->toBe($match->id);
});

test('product filter supports stock states and safe sorting', function () {
    $inStock = createOmsProduct(['name' => 'In Stock', 'price' => 200, 'stock_quantity' => 10, 'low_stock_threshold' => 3]);
    $lowStock = createOmsProduct(['name' => 'Low Stock', 'price' => 100, 'stock_quantity' => 2, 'low_stock_threshold' => 3]);
    $noStock = createOmsProduct(['name' => 'No Stock', 'price' => 300, 'stock_quantity' => 0, 'low_stock_threshold' => 3]);

    expect(paginatedProducts(['filters' => ['stock_status' => 'in_stock']])->items()[0]->id)->toBe($inStock->id)
        ->and(paginatedProducts(['filters' => ['stock_status' => 'low_stock']])->items()[0]->id)->toBe($lowStock->id)
        ->and(paginatedProducts(['filters' => ['stock_status' => 'no_stock']])->items()[0]->id)->toBe($noStock->id);

    $priceAsc = collect(paginatedProducts(['sorts' => ['price' => 'asc']])->items())->pluck('id')->all();
    expect($priceAsc)->toBe([$lowStock->id, $inStock->id, $noStock->id]);

    $invalidSort = paginatedProducts(['sorts' => ['not_a_column' => 'sideways']]);
    expect($invalidSort->total())->toBe(Product::count());
});

test('product repository excludes soft deleted products by default', function () {
    $active = createOmsProduct(['name' => 'Visible']);
    $deleted = createOmsProduct(['name' => 'Deleted']);
    $deleted->delete();

    $ids = collect(paginatedProducts([])->items())->pluck('id');

    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain($deleted->id);
});
