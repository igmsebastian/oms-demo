<?php

use App\Enums\InventoryChangeType;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundStockDisposition;
use App\Models\InventoryLog;
use App\Models\OrderActivity;
use App\Models\OrderRefund;
use App\Models\User;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function deliveredOmsOrder(User $user, User $admin, int $quantity = 1): array
{
    $product = createOmsProduct(['stock_quantity' => 10]);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => $quantity]]);
    $orders = app(OrderService::class);
    $orders->fulfillOrder($order, $admin);
    $orders->updateStatus($order->fresh(), OrderStatus::Shipped, $admin);
    $orders->updateStatus($order->fresh(), OrderStatus::Delivered, $admin);

    return [$order->fresh(), $product];
}

test('customers can request refunds only for their delivered orders', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $other = User::factory()->create();
    [$order] = deliveredOmsOrder($user, $admin);

    $this->actingAs($user)
        ->post(route('orders.refunds.store', ['order' => $order->order_number]), [
            'amount' => 120.00,
            'reason' => 'Wrong size.',
        ])
        ->assertRedirect(route('orders.show', ['order' => $order->order_number]));

    expect($order->fresh()->status)->toBe(OrderStatus::RefundPending)
        ->and(OrderRefund::first()?->status)->toBe(RefundStatus::Pending)
        ->and(OrderActivity::where('event', OrderActivityEvent::RefundRequested->value)->exists())->toBeTrue();

    $anotherOrder = createOmsOrder($user, [['product' => createOmsProduct()]]);
    $this->actingAs($user)
        ->post(route('orders.refunds.store', ['order' => $anotherOrder->order_number]), [
            'amount' => 1,
        ])
        ->assertForbidden();

    $this->actingAs($other)
        ->post(route('orders.refunds.store', ['order' => $order->order_number]), [
            'amount' => 1,
        ])
        ->assertForbidden();
});

test('admin can process and complete good stock refunds with inventory restoration', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    [$order, $product] = deliveredOmsOrder($user, $admin, 2);
    $refund = app(OrderRefundService::class)->createRefund($order, $user, [
        'amount' => 240.00,
        'reason' => 'Returned unopened.',
    ]);

    app(OrderRefundService::class)->markProcessing($refund, $admin);
    app(OrderRefundService::class)->markCompleted($refund->fresh(), $admin, RefundStockDisposition::GoodStock, 'Good stock return.');

    expect($refund->fresh()->status)->toBe(RefundStatus::Completed)
        ->and($refund->fresh()->processed_by_user_id)->toBe($admin->id)
        ->and($refund->fresh()->metadata[RefundStockDisposition::MetadataKey])->toBe('good_stock')
        ->and($order->items()->first()->refunded_quantity)->toBe(2)
        ->and($product->fresh()->stock_quantity)->toBe(10)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($order->fresh()->refunded_at)->not->toBeNull()
        ->and(InventoryLog::where('change_type', InventoryChangeType::Restore->value)->exists())->toBeTrue()
        ->and(OrderActivity::where('event', OrderActivityEvent::RefundProcessing->value)->exists())->toBeTrue()
        ->and(OrderActivity::where('event', OrderActivityEvent::RefundCompleted->value)->exists())->toBeTrue();
});

test('bad stock refunds do not restore inventory and completed refunds are idempotent', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    [$order, $product] = deliveredOmsOrder($user, $admin);
    $refund = app(OrderRefundService::class)->createRefund($order, $admin, [
        'amount' => 120.00,
        'reason' => 'Damaged.',
    ]);

    app(OrderRefundService::class)->markCompleted($refund, $admin, RefundStockDisposition::BadStock, 'Damaged pair.');

    expect($product->fresh()->stock_quantity)->toBe(9)
        ->and(InventoryLog::where('change_type', InventoryChangeType::Restore->value)->count())->toBe(0);

    expect(fn () => app(OrderRefundService::class)->markCompleted($refund->fresh(), $admin, RefundStockDisposition::GoodStock))
        ->toThrow(ValidationException::class);
});

test('refund processing rejects invalid state and users cannot complete refunds through route', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    [$order] = deliveredOmsOrder($user, $admin);
    $refund = app(OrderRefundService::class)->createRefund($order, $user, ['amount' => 120.00]);
    app(OrderRefundService::class)->markProcessing($refund, $admin);

    expect(fn () => app(OrderRefundService::class)->markProcessing($refund->fresh(), $admin))
        ->toThrow(ValidationException::class);

    $this->actingAs($user)
        ->patch(route('refunds.completed', $refund), [
            'stock_disposition' => RefundStockDisposition::GoodStock->value,
        ])
        ->assertForbidden();
});
