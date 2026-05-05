<?php

use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundStockDisposition;
use App\Models\OrderRefund;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('dashboard inertia props include kpis and charts', function () {
    $user = User::factory()->create();
    createOmsOrder($user, [['product' => createOmsProduct()]]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/index')
            ->has('dashboard.kpis')
            ->has('dashboard.revenue_series')
            ->has('dashboard.status_chart')
        );
});

test('orders inertia page includes filters status counts and user scoping', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $ownOrder = createOmsOrder($owner, [['product' => createOmsProduct()]]);
    createOmsOrder($other, [['product' => createOmsProduct()]]);
    app(OrderService::class)->confirmOrder($ownOrder, $admin);

    $this->actingAs($owner)
        ->get(route('orders.index', ['filters' => ['statuses' => [OrderStatus::Confirmed->nameValue()]]]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Orders/index')
            ->where('is_admin', false)
            ->has('status_counts')
            ->has('status_options')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $ownOrder->id)
        );
});

test('order details inertia props include relations and role-specific actions', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = createOmsOrder($owner, [['product' => createOmsProduct()]]);

    $this->actingAs($owner)
        ->get(route('orders.show', ['order' => $order->order_number]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderDetails/index')
            ->where('order.id', $order->id)
            ->has('order.customer')
            ->has('order.items')
            ->has('order.activities')
            ->has('order.refunds')
            ->has('order.allowed_actions')
        );

    $this->actingAs($admin)
        ->get(route('orders.show', ['order' => $order->order_number]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('order.allowed_actions.0', 'fulfill'));

    $this->actingAs($other)
        ->get(route('orders.show', ['order' => $order->order_number]))
        ->assertForbidden();
});

test('order details exposes refund stock disposition for item ledger context', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $order = createOmsOrder($owner, [['product' => createOmsProduct()]]);

    OrderRefund::query()->create([
        'order_id' => $order->id,
        'requested_by_user_id' => $owner->id,
        'processed_by_user_id' => $admin->id,
        'status' => RefundStatus::Completed,
        'amount' => $order->total_amount,
        'reason' => 'Returned damaged.',
        'metadata' => [
            RefundStockDisposition::MetadataKey => RefundStockDisposition::BadStock->value,
        ],
        'processed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('orders.show', ['order' => $order->order_number]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.refunds.0.stock_disposition', RefundStockDisposition::BadStock->value)
        );
});

test('admin inventory reports and product management pages expose expected props', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Products/index')
            ->has('products')
            ->has('metrics')
            ->has('references')
        );

    $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/index')
            ->has('reports')
            ->has('filters.date_from')
            ->has('filters.date_to')
        );

    $this->actingAs($admin)
        ->get(route('product-management.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ProductManagement/index')
            ->has('modules.categories.records')
            ->has('modules.brands.records')
            ->has('modules.tags.records')
        );
});
