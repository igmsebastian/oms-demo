<?php

use App\Enums\OrderStatus;
use App\Models\OrderStatusReference;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Schema;

test('order status seeder syncs enum cases idempotently', function () {
    $this->seed(OrderStatusSeeder::class);
    $this->seed(OrderStatusSeeder::class);

    expect(OrderStatusReference::count())->toBe(count(OrderStatus::cases()));

    foreach (OrderStatus::cases() as $status) {
        $record = OrderStatusReference::find($status->value);

        expect($record)->not->toBeNull()
            ->and($record->name)->toBe($status->nameValue())
            ->and($record->label)->toBe($status->label())
            ->and($record->sort_order)->toBe($status->value)
            ->and($record->is_active)->toBeTrue();
    }
});

test('order status schema uses integer references while orders cast status to enum', function () {
    $this->seed(OrderStatusSeeder::class);

    $user = User::factory()->create();
    $product = createOmsProduct();
    $order = createOmsOrder($user, [['product' => $product]]);

    expect(Schema::getColumnType('order_statuses', 'id'))->toBe('integer')
        ->and(Schema::getColumnType('orders', 'status'))->toBe('integer')
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});
