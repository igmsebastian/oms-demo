<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\OrderActivity;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    Mail::fake();
});

test('order activities track status transitions', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 4);
    $order = createLifecycleOrder($user, $product, 1);

    app(OrderService::class)->confirmOrder($order, $admin);

    $activity = OrderActivity::where('event', OrderActivityEvent::OrderConfirmed->value)->first();

    expect($activity->from_status)->toBe(OrderStatus::Pending)
        ->and($activity->to_status)->toBe(OrderStatus::Confirmed)
        ->and($activity->actor_id)->toBe($admin->id);
});
