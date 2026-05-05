<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\OrderActivity;
use App\Models\User;
use App\Services\OrderService;
use App\Services\OrderStatusTransitionService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('allowed transitions create activities actors notes and timestamps', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 5]);
    $order = createOmsOrder($user, [['product' => $product]]);

    app(OrderService::class)->confirmOrder($order, $admin, 'Payment cleared.');
    $packed = app(OrderStatusTransitionService::class)->transition($order->fresh(), OrderStatus::Processing, $admin);
    $packed = app(OrderStatusTransitionService::class)->transition($packed, OrderStatus::Packed, $admin, [
        'description' => 'Packed securely.',
    ]);

    $activity = OrderActivity::where('order_id', $order->id)
        ->where('event', OrderActivityEvent::OrderPacked->value)
        ->firstOrFail();

    expect($packed->status)->toBe(OrderStatus::Packed)
        ->and($order->fresh()->confirmed_at)->not->toBeNull()
        ->and($activity->actor_id)->toBe($admin->id)
        ->and($activity->actor_role)->toBe($admin->role)
        ->and($activity->from_status)->toBe(OrderStatus::Processing)
        ->and($activity->to_status)->toBe(OrderStatus::Packed)
        ->and($activity->description)->toBe('Packed securely.');
});

test('forbidden transitions throw validation errors without changing status', function (OrderStatus $from, OrderStatus $to) {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);
    $order->forceFill(['status' => $from])->save();

    expect(fn () => app(OrderStatusTransitionService::class)->transition($order->fresh(), $to, $admin))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe($from);
})->with([
    'pending to shipped' => [OrderStatus::Pending, OrderStatus::Shipped],
    'pending to delivered' => [OrderStatus::Pending, OrderStatus::Delivered],
    'processing to delivered' => [OrderStatus::Processing, OrderStatus::Delivered],
    'shipped to processing' => [OrderStatus::Shipped, OrderStatus::Processing],
    'completed to processing' => [OrderStatus::Completed, OrderStatus::Processing],
    'cancelled to processing' => [OrderStatus::Cancelled, OrderStatus::Processing],
    'refunded to processing' => [OrderStatus::Refunded, OrderStatus::Processing],
]);

test('frontend allowed actions come from the same transition map', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);
    $order->forceFill(['status' => OrderStatus::Processing])->save();
    $request = Request::create(route('orders.show', ['order' => $order->order_number]));
    $request->setUserResolver(fn () => $admin);

    $payload = OrderResource::make($order->fresh()->load('refunds'))->resolve($request);

    expect(collect($payload['available_statuses'])->pluck('name')->all())->toContain('packed', 'shipped')
        ->and($payload['allowed_actions'])->toContain('mark_packed', 'mark_shipped');
});
