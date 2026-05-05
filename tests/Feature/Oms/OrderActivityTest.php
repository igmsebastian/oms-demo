<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Filters\OrderActivityFilter;
use App\Models\User;
use App\Repositories\OrderActivityRepository;
use App\Services\OrderActivityService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('activity service records actor status metadata and system events', function () {
    $actor = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $order = createOmsOrder($customer, [['product' => createOmsProduct()]]);

    $activity = app(OrderActivityService::class)->record($order, OrderActivityEvent::RemarkAdded, [
        'actor' => $actor,
        'title' => 'Remark added',
        'description' => 'Manual note.',
        'from_status' => OrderStatus::Pending,
        'to_status' => OrderStatus::Confirmed,
        'metadata' => ['source' => 'test'],
    ]);

    $system = app(OrderActivityService::class)->record($order, OrderActivityEvent::OrderCompleted, [
        'description' => 'System job.',
        'to_status' => OrderStatus::Completed,
        'metadata' => ['system' => true],
    ]);

    expect($activity->order_id)->toBe($order->id)
        ->and($activity->actor_id)->toBe($actor->id)
        ->and($activity->actor_role)->toBe($actor->role)
        ->and($activity->event)->toBe(OrderActivityEvent::RemarkAdded)
        ->and($activity->from_status)->toBe(OrderStatus::Pending)
        ->and($activity->to_status)->toBe(OrderStatus::Confirmed)
        ->and($activity->metadata)->toBe(['source' => 'test'])
        ->and($system->actor_id)->toBeNull()
        ->and($system->metadata)->toBe(['system' => true]);
});

test('activity repository returns latest records first and keeps remarks in the same feed', function () {
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    $old = app(OrderActivityService::class)->record($order, OrderActivityEvent::OrderCreated, [
        'description' => 'Old',
    ]);
    $new = app(OrderActivityService::class)->record($order, OrderActivityEvent::RemarkAdded, [
        'description' => 'Newest remark',
    ]);
    $old->forceFill(['created_at' => now()->subMinute()])->save();
    $new->forceFill(['created_at' => now()->addMinute()])->save();

    $filter = new OrderActivityFilter(Request::create('/activities'));
    $rows = app(OrderActivityRepository::class)->paginate($filter)->items();

    expect($rows[0]->id)->toBe($new->id)
        ->and(collect($rows)->pluck('event'))->toContain(OrderActivityEvent::RemarkAdded);
});
