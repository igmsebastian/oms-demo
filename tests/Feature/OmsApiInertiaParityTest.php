<?php

use App\Models\Product;
use App\Models\User;

test('api and inertia product controllers use the same product rules', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/products', [
            'sku' => 'SKU-API',
            'name' => 'API Product',
            'price' => 12.00,
            'stock_quantity' => 4,
        ])
        ->assertSuccessful();

    $this->actingAs($admin)
        ->post('/admin/products', [
            'sku' => 'SKU-INERTIA',
            'name' => 'Inertia Product',
            'price' => 12.00,
            'stock_quantity' => 4,
        ])
        ->assertRedirect(route('admin.products.index'));

    expect(Product::whereIn('sku', ['SKU-API', 'SKU-INERTIA'])->count())->toBe(2);
});
