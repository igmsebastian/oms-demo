<?php

use App\Enums\OrderStatus;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('oms primary keys and status columns use expected database shapes', function () {
    $user = User::factory()->create();
    $product = createOmsProduct();
    $order = createOmsOrder($user, [['product' => $product]]);

    expect(Str::isUlid($user->id))->toBeTrue()
        ->and(Str::isUlid($product->id))->toBeTrue()
        ->and(Str::isUlid($order->id))->toBeTrue()
        ->and(Schema::getColumnType('order_statuses', 'id'))->toBe('integer')
        ->and(Schema::getColumnType('orders', 'status'))->toBe('integer')
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('schema contains inventory and order item integrity columns used by validation and services', function () {
    expect(Schema::hasColumns('products', [
        'price',
        'stock_quantity',
        'low_stock_threshold',
        'deleted_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('order_items', [
            'quantity',
            'cancelled_quantity',
            'refunded_quantity',
            'unit_price',
            'line_total',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('personal_access_tokens', [
            'tokenable_type',
            'tokenable_id',
        ]))->toBeTrue();
});
