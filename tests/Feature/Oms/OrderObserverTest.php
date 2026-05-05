<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function rawOrderPayload(User $user, array $overrides = []): array
{
    return [
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
        'total_amount' => 25.00,
        'shipping_address_line_1' => '123 Test Street',
        'shipping_city' => 'Test City',
        'shipping_country' => 'United States',
        'shipping_post_code' => '10001',
        'shipping_full_address' => '123 Test Street, Test City, United States, 10001',
        ...$overrides,
    ];
}

test('order observer generates unique ulid orders with expected number format', function () {
    $user = User::factory()->create();

    $orders = collect(range(1, 3))->map(fn (): Order => Order::create(rawOrderPayload($user)));

    expect($orders->pluck('order_number')->unique())->toHaveCount(3);

    $orders->each(function (Order $order): void {
        expect(Str::isUlid($order->id))->toBeTrue()
            ->and($order->order_number)->toMatch('/^ORD-\d{8}-[A-Z0-9]{6}$/');
    });
});

test('order observer preserves supplied order number and retries collisions', function () {
    $user = User::factory()->create();
    $date = now()->format('Ymd');

    Order::create(rawOrderPayload($user, ['order_number' => "ORD-{$date}-ABC123"]));

    Str::createRandomStringsUsingSequence(['ABC123', 'XYZ789']);

    try {
        $order = Order::create(rawOrderPayload($user));
    } finally {
        Str::createRandomStringsNormally();
    }

    expect($order->order_number)->toBe("ORD-{$date}-XYZ789");
});
