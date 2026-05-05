<?php

use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('user addresses use ulids belong to users and format full addresses cleanly', function () {
    $user = User::factory()->create();
    $address = createOmsAddress($user, ['address_line_2' => null]);

    expect(Str::isUlid($address->id))->toBeTrue()
        ->and($address->user->is($user))->toBeTrue()
        ->and($address->full_address)->toBe('522 South Spring Street, Los Angeles, United States, 90013');
});

test('soft deleted addresses cannot be used for new orders', function () {
    $user = User::factory()->create();
    $product = createOmsProduct();
    $address = createOmsAddress($user);
    $address->delete();

    expect(fn () => createOmsOrder($user, [['product' => $product]], ['address' => $address->id]))
        ->toThrow(ModelNotFoundException::class);
});

test('orders store a shipping snapshot that does not change when address changes', function () {
    $user = User::factory()->create();
    $product = createOmsProduct();
    $address = createOmsAddress($user, ['address_line_1' => 'Original Street']);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 2]], ['address' => $address]);

    $address->update([
        'address_line_1' => 'Edited Street',
        'city' => 'San Diego',
    ]);

    expect($order->fresh()->shipping_address_line_1)->toBe('Original Street')
        ->and($order->fresh()->shipping_full_address)->toContain('Original Street')
        ->and($order->fresh()->shipping_full_address)->not->toContain('Edited Street');
});
