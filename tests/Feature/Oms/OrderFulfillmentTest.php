<?php

use App\Enums\InventoryChangeType;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Jobs\SendConfiguredOrderEmailJob;
use App\Models\InventoryLog;
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

test('admin fulfillment moves pending order to processing and deducts every item atomically', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $first = createOmsProduct(['stock_quantity' => 8, 'low_stock_threshold' => 1]);
    $second = createOmsProduct(['stock_quantity' => 6, 'low_stock_threshold' => 1]);
    $order = createOmsOrder($user, [
        ['product' => $first, 'quantity' => 2],
        ['product' => $second, 'quantity' => 3],
    ]);

    app(OrderService::class)->fulfillOrder($order, $admin, 'Warehouse assignment approved.');

    expect($order->fresh()->status)->toBe(OrderStatus::Processing)
        ->and($first->fresh()->stock_quantity)->toBe(6)
        ->and($second->fresh()->stock_quantity)->toBe(3)
        ->and(InventoryLog::where('change_type', InventoryChangeType::Deduction->value)->count())->toBe(2)
        ->and(OrderActivity::where('order_id', $order->id)->where('event', OrderActivityEvent::OrderConfirmed->value)->exists())->toBeTrue()
        ->and(OrderActivity::where('order_id', $order->id)->where('event', OrderActivityEvent::OrderProcessingStarted->value)->where('description', 'Warehouse assignment approved.')->exists())->toBeTrue();

    Bus::assertDispatched(SendConfiguredOrderEmailJob::class, fn (SendConfiguredOrderEmailJob $job): bool => $job->emailKey === 'order_confirmed'
        && $job->recipient->is($user));
    Bus::assertDispatched(SendConfiguredOrderEmailJob::class, fn (SendConfiguredOrderEmailJob $job): bool => $job->emailKey === 'order_processing'
        && $job->recipient->is($user));
});

test('users cannot fulfill orders through web route', function () {
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    $this->actingAs($user)
        ->post(route('orders.fulfill', ['order' => $order->order_number]), ['note' => 'Try'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('fulfillment rolls back all item deductions if any item has insufficient stock', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $first = createOmsProduct(['stock_quantity' => 5, 'low_stock_threshold' => 1]);
    $second = createOmsProduct(['stock_quantity' => 1]);
    $order = createOmsOrder($user, [
        ['product' => $first, 'quantity' => 2],
        ['product' => $second, 'quantity' => 2],
    ]);

    expect(fn () => app(OrderService::class)->fulfillOrder($order, $admin))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($first->fresh()->stock_quantity)->toBe(5)
        ->and($second->fresh()->stock_quantity)->toBe(1)
        ->and(InventoryLog::count())->toBe(0);
});

test('fulfillment is idempotent and cannot double deduct already processing orders', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 5, 'low_stock_threshold' => 1]);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 2]]);

    app(OrderService::class)->fulfillOrder($order, $admin);

    expect(fn () => app(OrderService::class)->fulfillOrder($order->fresh(), $admin))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->stock_quantity)->toBe(3)
        ->and(InventoryLog::count())->toBe(1);
});
