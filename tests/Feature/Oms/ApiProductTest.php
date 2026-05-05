<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('api products are authenticated paginated filterable and policy protected', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create();
    $match = createOmsProduct([
        'sku' => 'API-FILTER-001',
        'name' => 'API Filter Product',
        'product_category_id' => $category->id,
    ]);
    createOmsProduct(['sku' => 'API-OTHER-001', 'name' => 'Other Product']);

    $this->getJson('/api/products')->assertUnauthorized();

    $this->actingAs($admin)
        ->getJson('/api/products?'.http_build_query([
            'filters' => [
                'keyword' => 'API-FILTER',
                'category_id' => $category->id,
            ],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonStructure(['data' => [['id', 'sku', 'name', 'price', 'stock_quantity']]]);

    $this->actingAs($user)
        ->postJson('/api/products', [
            'sku' => 'API-DENIED',
            'name' => 'Denied',
            'price' => 99,
            'stock_quantity' => 1,
        ])
        ->assertForbidden();
});

test('api product store update and delete enforce validation and mutate database state', function () {
    $admin = User::factory()->admin()->create();
    $product = createOmsProduct(['sku' => 'API-UPDATE-001']);

    $this->actingAs($admin)
        ->postJson('/api/products', [
            'sku' => 'API-CREATED-001',
            'name' => 'API Created Product',
            'price' => 50.25,
            'stock_quantity' => 7,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.sku', 'API-CREATED-001');

    $this->actingAs($admin)
        ->postJson('/api/products', [
            'sku' => 'API-CREATED-001',
            'price' => -1,
            'stock_quantity' => -1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sku', 'name', 'price', 'stock_quantity']);

    $this->actingAs($admin)
        ->patchJson("/api/products/{$product->id}", [
            'name' => 'Updated API Product',
            'price' => 88,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated API Product');

    $this->actingAs($admin)
        ->deleteJson("/api/products/{$product->id}")
        ->assertNoContent();

    expect(Product::withTrashed()->find($product->id)?->trashed())->toBeTrue();
});
