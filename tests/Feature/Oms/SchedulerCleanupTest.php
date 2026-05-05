<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\OrderActivity;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('cleanup command completes old delivered orders and leaves new delivered orders alone', function () {
    $user = User::factory()->create();
    $old = createOmsOrder($user, [['product' => createOmsProduct()]]);
    $new = createOmsOrder($user, [['product' => createOmsProduct()]]);
    $old->forceFill(['status' => OrderStatus::Delivered, 'updated_at' => now()->subDays(8)])->save();
    $new->forceFill(['status' => OrderStatus::Delivered, 'updated_at' => now()])->save();

    $this->artisan('orders:cleanup-statuses')->assertSuccessful();
    $this->artisan('orders:cleanup-statuses')->assertSuccessful();

    expect($old->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($new->fresh()->status)->toBe(OrderStatus::Delivered)
        ->and(OrderActivity::where('order_id', $old->id)->whereNull('actor_id')->where('event', OrderActivityEvent::OrderCompleted->value)->count())->toBe(1);
});

test('cleanup command only cancels partially cancelled orders when all quantities are cancelled', function () {
    $user = User::factory()->create();
    $fully = createOmsOrder($user, [['product' => createOmsProduct(), 'quantity' => 2]]);
    $partially = createOmsOrder($user, [['product' => createOmsProduct(), 'quantity' => 2]]);
    $fully->forceFill(['status' => OrderStatus::PartiallyCancelled])->save();
    $fully->items()->first()->forceFill(['cancelled_quantity' => 2])->save();
    $partially->forceFill(['status' => OrderStatus::PartiallyCancelled])->save();
    $partially->items()->first()->forceFill(['cancelled_quantity' => 1])->save();

    $this->artisan('orders:cleanup-statuses')->assertSuccessful();

    expect($fully->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($partially->fresh()->status)->toBe(OrderStatus::PartiallyCancelled);
});
