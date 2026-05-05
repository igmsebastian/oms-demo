<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStockDisposition;
use App\Models\OrderActivity;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('api orders expose admin all-orders and customer own-orders behavior', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $ownOrder = createOmsOrder($owner, [['product' => createOmsProduct()]]);
    $otherOrder = createOmsOrder($other, [['product' => createOmsProduct()]]);

    $this->getJson('/api/orders')->assertUnauthorized();

    $this->actingAs($owner)
        ->getJson('/api/orders')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownOrder->id);

    $this->actingAs($admin)
        ->getJson('/api/orders')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->actingAs($owner)->getJson("/api/orders/{$otherOrder->id}")->assertForbidden();
});

test('api order create show remark fulfill status cancel and refund endpoints mutate expected state', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 5]);

    $response = $this->actingAs($user)
        ->postJson('/api/orders', [
            'shipping_address_line_1' => 'API Street',
            'shipping_city' => 'API City',
            'shipping_country' => 'United States',
            'shipping_post_code' => '10001',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.name', 'pending');

    $orderId = $response->json('data.id');

    $this->actingAs($user)
        ->postJson("/api/orders/{$orderId}/remarks", ['note' => 'API remark'])
        ->assertSuccessful();

    $this->actingAs($user)
        ->postJson("/api/orders/{$orderId}/fulfill", ['note' => 'Denied'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->postJson("/api/orders/{$orderId}/fulfill", ['note' => 'Start'])
        ->assertSuccessful()
        ->assertJsonPath('data.status.name', 'processing');

    $this->actingAs($admin)
        ->patchJson("/api/orders/{$orderId}/status", ['status' => OrderStatus::Shipped->value])
        ->assertSuccessful()
        ->assertJsonPath('data.status.name', 'shipped');

    expect($product->fresh()->stock_quantity)->toBe(3)
        ->and(OrderActivity::where('event', OrderActivityEvent::RemarkAdded->value)->exists())->toBeTrue();
});

test('api validates order creation and refund completion payloads', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    $this->actingAs($user)
        ->postJson('/api/orders', ['items' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items', 'shipping_address_line_1', 'shipping_city']);

    $order->forceFill(['status' => OrderStatus::Delivered])->save();
    $refundResponse = $this->actingAs($user)
        ->postJson("/api/orders/{$order->id}/refunds", [
            'amount' => 120.00,
            'reason' => 'Returned.',
        ])
        ->assertSuccessful();

    $refundId = $refundResponse->json('data.refunds.0.id');

    $this->actingAs($admin)
        ->patchJson("/api/refunds/{$refundId}/completed", [
            'stock_disposition' => 'pending_review',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('stock_disposition');

    $this->actingAs($admin)
        ->patchJson("/api/refunds/{$refundId}/completed", [
            'stock_disposition' => RefundStockDisposition::BadStock->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.name', 'refunded');
});
