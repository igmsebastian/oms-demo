<?php

use App\Enums\OrderStatus;
use App\Models\InventoryLog;
use App\Models\User;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('failed fulfillment rolls back all deductions and status changes', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $good = createOmsProduct(['stock_quantity' => 4]);
    $bad = createOmsProduct(['stock_quantity' => 0]);
    $order = createOmsOrder($user, [
        ['product' => $good, 'quantity' => 2],
        ['product' => $bad, 'quantity' => 1],
    ]);

    expect(fn () => app(OrderService::class)->fulfillOrder($order, $admin))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($good->fresh()->stock_quantity)->toBe(4)
        ->and($bad->fresh()->stock_quantity)->toBe(0)
        ->and(InventoryLog::count())->toBe(0);
});

test('repeated cancellation and refund completion do not duplicate inventory restoration', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 5]);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 2]]);

    app(OrderService::class)->confirmOrder($order, $admin);
    app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Cancelled once.');

    expect(fn () => app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Again'))
        ->toThrow(ValidationException::class);

    $refund = app(OrderRefundService::class)->createRefund($order->fresh(), $admin, ['amount' => 240.00]);
    app(OrderRefundService::class)->markCompleted($refund, $admin);

    expect(fn () => app(OrderRefundService::class)->markCompleted($refund->fresh(), $admin))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->stock_quantity)->toBe(5)
        ->and(InventoryLog::where('quantity_change', 2)->count())->toBe(1);
});
