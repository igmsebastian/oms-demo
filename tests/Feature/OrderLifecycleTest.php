<?php

use App\Enums\OrderStatus;
use App\Models\InventoryLog;
use App\Models\OrderActivity;
use App\Models\User;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    Mail::fake();
});

test('orders are created with product and shipping snapshots', function () {
    $user = User::factory()->create();
    $product = createLifecycleProduct();
    $order = createLifecycleOrder($user, $product, 2);

    expect($order->order_number)->toStartWith('ORD-')
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->shipping_full_address)->toContain('123 Test Street')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->product_name)->toBe($product->name)
        ->and($order->items->first()->product_sku)->toBe($product->sku);
});

test('order confirmation deducts inventory and creates logs', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product, 2);

    $confirmed = app(OrderService::class)->confirmOrder($order, $admin);
    $log = InventoryLog::first();

    expect($confirmed->status)->toBe(OrderStatus::Confirmed)
        ->and($product->fresh()->stock_quantity)->toBe(3)
        ->and(InventoryLog::count())->toBe(1)
        ->and($log->changed_by_user_id)->toBe($admin->id)
        ->and($log->stock_before)->toBe(5)
        ->and($log->stock_after)->toBe(3)
        ->and(OrderActivity::where('order_id', $order->id)->count())->toBeGreaterThan(1);
});

test('insufficient stock prevents confirmation', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 1);
    $order = createLifecycleOrder($user, $product, 2);

    expect(fn () => app(OrderService::class)->confirmOrder($order, $admin))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->stock_quantity)->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('full cancellation restores confirmed inventory once', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product, 2);

    app(OrderService::class)->confirmOrder($order, $admin);
    app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Customer request');

    expect($product->fresh()->stock_quantity)->toBe(5)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->items()->first()->cancelled_quantity)->toBe(2);

    expect(fn () => app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Again'))
        ->toThrow(ValidationException::class);
});

test('partial cancellation restores only cancelled quantity', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 10);
    $order = createLifecycleOrder($user, $product, 3);

    app(OrderService::class)->confirmOrder($order, $admin);
    $item = $order->items()->first();
    app(OrderCancellationService::class)->partiallyCancelItem($item, 1, $admin, 'One item unavailable');

    expect($product->fresh()->stock_quantity)->toBe(8)
        ->and($item->fresh()->cancelled_quantity)->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::PartiallyCancelled);

    expect(fn () => app(OrderCancellationService::class)->partiallyCancelItem($item->fresh(), 3, $admin, 'Too much'))
        ->toThrow(ValidationException::class);
});

test('refunded orders cannot be refunded twice', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product, 1);

    app(OrderService::class)->confirmOrder($order, $admin);
    app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Cancelled');
    $refund = app(OrderRefundService::class)->createRefund($order->fresh(), $admin, [
        'amount' => 10.00,
        'reason' => 'Cancelled',
    ]);

    app(OrderRefundService::class)->markCompleted($refund, $admin);

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);

    expect(fn () => app(OrderRefundService::class)->createRefund($order->fresh(), $admin, [
        'amount' => 10.00,
    ]))->toThrow(ValidationException::class);
});
