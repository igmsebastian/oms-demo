<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Jobs\SendConfiguredOrderEmailJob;
use App\Models\OrderActivity;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    Bus::fake();
});

test('customer order creation calculates totals snapshots products and address without deducting stock', function () {
    $user = User::factory()->create();
    $product = createOmsProduct(['name' => 'Apex Runner', 'sku' => 'APEX-001', 'price' => 125.50, 'stock_quantity' => 9]);
    $address = createOmsAddress($user, ['address_line_1' => 'Snapshot Street']);

    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 2]], ['address' => $address]);
    $item = $order->items()->first();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->order_number)->toStartWith('ORD-')
        ->and($order->total_amount)->toBe('251.00')
        ->and($item->unit_price)->toBe('125.50')
        ->and($item->line_total)->toBe('251.00')
        ->and($item->product_name)->toBe('Apex Runner')
        ->and($item->product_sku)->toBe('APEX-001')
        ->and($order->shipping_full_address)->toContain('Snapshot Street')
        ->and($product->fresh()->stock_quantity)->toBe(9)
        ->and(OrderActivity::where('order_id', $order->id)->where('event', OrderActivityEvent::OrderCreated->value)->exists())->toBeTrue();

    Bus::assertDispatched(SendConfiguredOrderEmailJob::class, fn (SendConfiguredOrderEmailJob $job): bool => $job->emailKey === 'order_created'
        && $job->recipient->is($user));
});

test('order creation rejects empty items invalid inactive products and invalid quantities', function () {
    $user = User::factory()->create();
    $inactive = createOmsProduct(['is_active' => false]);

    expect(fn () => app(OrderService::class)->createOrder($user, [
        'shipping_address_line_1' => 'Street',
        'shipping_city' => 'City',
        'shipping_country' => 'United States',
        'shipping_post_code' => '10001',
        'items' => [],
    ]))->toThrow(ValidationException::class);

    expect(fn () => createOmsOrder($user, [['product' => $inactive, 'quantity' => 1]]))
        ->toThrow(ValidationException::class);

    expect(fn () => createOmsOrder($user, [['product' => createOmsProduct(), 'quantity' => 0]]))
        ->toThrow(ValidationException::class);
});

test('price and address edits after order creation do not change historical snapshots', function () {
    $user = User::factory()->create();
    $product = createOmsProduct(['price' => 88.00]);
    $address = createOmsAddress($user, ['address_line_1' => 'Original Address']);
    $order = createOmsOrder($user, [['product' => $product]], ['address' => $address]);

    $product->update(['price' => 150.00]);
    $address->update(['address_line_1' => 'Changed Address']);

    expect($order->items()->first()->unit_price)->toBe('88.00')
        ->and($order->fresh()->shipping_address_line_1)->toBe('Original Address');
});

test('guest cannot create orders through the api', function () {
    $product = createOmsProduct();

    $this->postJson('/api/orders', [
        'shipping_address_line_1' => 'Street',
        'shipping_city' => 'City',
        'shipping_country' => 'United States',
        'shipping_post_code' => '10001',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertUnauthorized();
});
