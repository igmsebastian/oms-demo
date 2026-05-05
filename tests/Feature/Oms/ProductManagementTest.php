<?php

use App\Models\ProductCategory;
use App\Models\User;
use App\Services\ProductTaxonomyService;
use Illuminate\Support\Facades\Cache;

test('taxonomy crud validates uniqueness and authorization', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('product-management.store', ['module' => 'categories']), ['name' => 'Lifestyle Sneakers'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('product-management.store', ['module' => 'categories']), [
            'name' => 'Lifestyle Sneakers',
            'description' => 'Daily wear category.',
            'is_active' => true,
        ])
        ->assertRedirect(route('product-management.index'));

    $category = ProductCategory::where('name', 'Lifestyle Sneakers')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('product-management.store', ['module' => 'categories']), ['name' => 'Lifestyle Sneakers'])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->patch(route('product-management.update', ['module' => 'categories', 'record' => $category->id]), [
            'name' => 'Lifestyle Sneakers',
            'description' => 'Updated.',
            'is_active' => true,
        ])
        ->assertRedirect(route('product-management.index'));

    expect($category->fresh()->slug)->toBe('lifestyle-sneakers');
});

test('taxonomy lists filter sort and invalidate reference caches', function () {
    $admin = User::factory()->admin()->create();
    ProductCategory::factory()->create(['name' => 'Running Shoes', 'slug' => 'running-shoes']);
    ProductCategory::factory()->create(['name' => 'Basketball Sneakers', 'slug' => 'basketball-sneakers']);

    app(ProductTaxonomyService::class)->references();
    expect(Cache::has(ProductTaxonomyService::REFERENCE_CACHE_KEY))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('product-management.index', ['keyword' => 'Running']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('modules.categories.records', 1)
            ->where('modules.categories.records.0.name', 'Running Shoes')
        );

    $this->actingAs($admin)
        ->delete(route('product-management.destroy', [
            'module' => 'categories',
            'record' => ProductCategory::where('slug', 'basketball-sneakers')->firstOrFail()->id,
        ]))
        ->assertRedirect(route('product-management.index'));

    expect(Cache::has(ProductTaxonomyService::REFERENCE_CACHE_KEY))->toBeFalse();
});

test('deleting taxonomy used by a product safely nulls the product reference', function () {
    $admin = User::factory()->admin()->create();
    $category = ProductCategory::factory()->create();
    $product = createOmsProduct(['product_category_id' => $category->id]);

    $this->actingAs($admin)
        ->delete(route('product-management.destroy', ['module' => 'categories', 'record' => $category->id]))
        ->assertRedirect(route('product-management.index'));

    expect($product->fresh()->product_category_id)->toBeNull();
});
