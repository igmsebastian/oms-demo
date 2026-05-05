<?php

use App\Enums\CancellationStatus;
use App\Enums\InventoryChangeType;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\InventoryLog;
use App\Models\OrderActivity;
use App\Models\OrderCancellation;
use App\Models\User;
use App\Services\OrderCancellationService;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('customer can request cancellation for own eligible order only once', function () {
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    app(OrderCancellationService::class)->requestCancellation($order, $user, 'Changed my mind.');

    $cancellation = OrderCancellation::firstOrFail();

    expect($order->fresh()->status)->toBe(OrderStatus::CancellationRequested)
        ->and($cancellation->status)->toBe(CancellationStatus::Requested)
        ->and($cancellation->reason)->toBe('Changed my mind.')
        ->and($cancellation->requested_by_user_id)->toBe($user->id)
        ->and($cancellation->requested_by_role)->toBe($user->role)
        ->and(OrderActivity::where('order_id', $order->id)->where('event', OrderActivityEvent::CancellationRequested->value)->exists())->toBeTrue();

    expect(fn () => app(OrderCancellationService::class)->requestCancellation($order->fresh(), $user, 'Again'))
        ->toThrow(ValidationException::class);
});

test('users cannot request cancellation for another user order through route', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = createOmsOrder($owner, [['product' => createOmsProduct()]]);

    $this->actingAs($other)
        ->post(route('orders.cancellation-requests.store', ['order' => $order->order_number]), [
            'reason' => 'Not mine.',
        ])
        ->assertForbidden();
});

test('admin cancellation restores confirmed inventory but pending cancellation does not restore undeducted stock', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $pendingProduct = createOmsProduct(['stock_quantity' => 5]);
    $confirmedProduct = createOmsProduct(['stock_quantity' => 5, 'low_stock_threshold' => 1]);
    $pending = createOmsOrder($user, [['product' => $pendingProduct, 'quantity' => 2]]);
    $confirmed = createOmsOrder($user, [['product' => $confirmedProduct, 'quantity' => 2]]);

    app(OrderCancellationService::class)->cancelOrder($pending, $admin, 'Customer cancelled.');
    app(OrderService::class)->confirmOrder($confirmed, $admin);
    app(OrderCancellationService::class)->cancelOrder($confirmed->fresh(), $admin, 'Fraud review.');

    expect($pendingProduct->fresh()->stock_quantity)->toBe(5)
        ->and($confirmedProduct->fresh()->stock_quantity)->toBe(5)
        ->and($pending->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($confirmed->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(InventoryLog::where('change_type', InventoryChangeType::Restore->value)->count())->toBe(1)
        ->and($confirmed->fresh()->cancelled_by_user_id)->toBe($admin->id)
        ->and($confirmed->fresh()->cancellation_reason)->toBe('Fraud review.');
});

test('partial cancellation restores only cancelled quantity and validates quantity', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 10]);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 3]]);
    app(OrderService::class)->confirmOrder($order, $admin);
    $item = $order->items()->first();

    app(OrderCancellationService::class)->partiallyCancelItem($item, 1, $admin, 'One pair unavailable.');

    expect($item->fresh()->cancelled_quantity)->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::PartiallyCancelled)
        ->and($product->fresh()->stock_quantity)->toBe(8)
        ->and(OrderActivity::where('event', OrderActivityEvent::OrderPartiallyCancelled->value)->exists())->toBeTrue();

    expect(fn () => app(OrderCancellationService::class)->partiallyCancelItem($item->fresh(), 99, $admin, 'Too many'))
        ->toThrow(ValidationException::class);
});
