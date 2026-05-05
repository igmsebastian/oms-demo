<?php

use App\Models\Product;
use App\Models\User;

test('admin can create products through the api', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/products', [
            'sku' => 'SKU-TEST-1',
            'name' => 'Test Product',
            'price' => 25.50,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
            'is_active' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.sku', 'SKU-TEST-1');

    expect(Product::where('sku', 'SKU-TEST-1')->exists())->toBeTrue();
});

test('customers cannot create products', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/products', [
            'sku' => 'SKU-DENIED',
            'name' => 'Denied Product',
            'price' => 25.50,
            'stock_quantity' => 10,
        ])
        ->assertForbidden();

    expect(Product::where('sku', 'SKU-DENIED')->exists())->toBeFalse();
});
