<?php

use App\Enums\OrderActivityEvent;
use App\Models\OrderActivity;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('owner and admin can add remarks and another customer cannot', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();
    $order = createOmsOrder($owner, [['product' => createOmsProduct()]]);

    $this->actingAs($owner)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => 'Owner note.'])
        ->assertRedirect(route('orders.show', ['order' => $order->order_number]));

    $this->actingAs($admin)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => 'Admin note.'])
        ->assertRedirect(route('orders.show', ['order' => $order->order_number]));

    $this->actingAs($other)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => 'Other note.'])
        ->assertForbidden();

    expect(OrderActivity::where('event', OrderActivityEvent::RemarkAdded->value)->count())->toBe(2)
        ->and(OrderActivity::where('actor_id', $admin->id)->where('description', 'Admin note.')->exists())->toBeTrue();
});

test('remark endpoint validates required max length and throttles spam', function () {
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    $this->actingAs($user)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => ''])
        ->assertSessionHasErrors('note');

    $this->actingAs($user)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => str_repeat('a', 301)])
        ->assertSessionHasErrors('note');

    foreach (range(1, 10) as $index) {
        $this->actingAs($user)
            ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => "Note {$index}"]);
    }

    $this->actingAs($user)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), ['note' => 'Too many'])
        ->assertTooManyRequests();
});
