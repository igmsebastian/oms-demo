<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createLifecycleProduct(int $stock = 10, int $threshold = 2): Product
{
    return Product::create([
        'sku' => fake()->unique()->bothify('SKU-####'),
        'name' => 'Lifecycle Product',
        'price' => 10.00,
        'stock_quantity' => $stock,
        'low_stock_threshold' => $threshold,
        'is_active' => true,
    ]);
}

function createLifecycleAddress(User $user): UserAddress
{
    return UserAddress::create([
        'user_id' => $user->id,
        'address_line_1' => '123 Test Street',
        'city' => 'Test City',
        'country' => 'United States',
        'post_code' => '10001',
        'is_default' => true,
    ]);
}

function createLifecycleOrder(User $user, Product $product, int $quantity = 1): Order
{
    $address = createLifecycleAddress($user);

    return app(OrderService::class)->createOrder($user, [
        'user_address_id' => $address->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => $quantity],
        ],
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createOmsProduct(array $attributes = []): Product
{
    return Product::create([
        'sku' => $attributes['sku'] ?? fake()->unique()->bothify('OMS-SKU-####'),
        'name' => $attributes['name'] ?? fake()->unique()->words(3, true),
        'description' => $attributes['description'] ?? fake()->sentence(),
        'price' => $attributes['price'] ?? 120.00,
        'stock_quantity' => $attributes['stock_quantity'] ?? 10,
        'low_stock_threshold' => $attributes['low_stock_threshold'] ?? 3,
        'is_active' => $attributes['is_active'] ?? true,
        'product_category_id' => $attributes['product_category_id'] ?? null,
        'product_brand_id' => $attributes['product_brand_id'] ?? null,
        'product_unit_id' => $attributes['product_unit_id'] ?? null,
        'product_size_id' => $attributes['product_size_id'] ?? null,
        'product_color_id' => $attributes['product_color_id'] ?? null,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createOmsAddress(User $user, array $attributes = []): UserAddress
{
    return UserAddress::create([
        'user_id' => $user->id,
        'address_line_1' => $attributes['address_line_1'] ?? '522 South Spring Street',
        'address_line_2' => array_key_exists('address_line_2', $attributes) ? $attributes['address_line_2'] : 'Loft 604',
        'city' => $attributes['city'] ?? 'Los Angeles',
        'country' => $attributes['country'] ?? 'United States',
        'post_code' => $attributes['post_code'] ?? '90013',
        'is_default' => $attributes['is_default'] ?? true,
    ]);
}

/**
 * @param  array<int, array{product: Product, quantity?: int}>  $items
 * @param  array<string, mixed>  $overrides
 */
function createOmsOrder(User $user, array $items, array $overrides = []): Order
{
    $address = $overrides['address'] ?? createOmsAddress($user);
    $payload = Arr::except($overrides, ['address']);

    return app(OrderService::class)->createOrder($user, [
        'user_address_id' => $address instanceof UserAddress ? $address->id : $address,
        'items' => collect($items)->map(fn (array $item): array => [
            'product_id' => $item['product']->id,
            'quantity' => $item['quantity'] ?? 1,
        ])->all(),
        ...$payload,
    ]);
}
