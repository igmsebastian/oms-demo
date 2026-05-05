<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStockDisposition;
use App\Models\OrderActivity;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\User;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use App\Services\ProductTaxonomyService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    Mail::fake();
});

test('root redirects guests and renders dashboard for authenticated users', function () {
    $this->get('/')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/index')
            ->has('dashboard.kpis')
        );
});

test('role aware pages protect admin only areas', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->get(route('products.index'))->assertOk();
    $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/index')
            ->has('reports.low_stock_count')
        );
    $this->actingAs($admin)->get(route('product-management.index'))->assertOk();

    $this->actingAs($user)->get(route('products.index'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($user)->get(route('product-management.index'))->assertForbidden();
});

test('orders can be filtered by multiple statuses', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 20);

    createLifecycleOrder($user, $product);

    $confirmed = createLifecycleOrder($user, $product);
    app(OrderService::class)->confirmOrder($confirmed, $admin);

    $processing = createLifecycleOrder($user, $product);
    app(OrderService::class)->fulfillOrder($processing, $admin);

    $statuses = [
        OrderStatus::Pending->nameValue(),
        OrderStatus::Confirmed->nameValue(),
    ];

    $this->actingAs($admin)
        ->get(route('orders.index', [
            'filters' => [
                'statuses' => $statuses,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Orders/index')
            ->where('filters.statuses', $statuses)
            ->has('orders.data', 2)
        );
});

test('order remarks are validated and recorded as activities', function () {
    $user = User::factory()->create();
    $product = createLifecycleProduct();
    $order = createLifecycleOrder($user, $product);

    $this->actingAs($user)
        ->get(route('orders.show', ['order' => $order->order_number]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderDetails/index')
            ->where('order.order_number', $order->order_number)
            ->where('order.id', $order->id)
            ->whereType('order.items', 'array')
            ->whereType('order.items.0.product.tags', 'array')
            ->whereType('order.activities', 'array')
            ->whereType('order.refunds', 'array')
            ->whereType('order.available_statuses', 'array')
        );

    $this->actingAs($user)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), [
            'note' => str_repeat('a', 301),
        ])
        ->assertSessionHasErrors('note');

    $this->actingAs($user)
        ->post(route('orders.remarks.store', ['order' => $order->order_number]), [
            'note' => 'Please leave at reception.',
        ])
        ->assertRedirect(route('orders.show', ['order' => $order->order_number]));

    expect(OrderActivity::where('order_id', $order->id)->where('event', OrderActivityEvent::RemarkAdded->value)->exists())->toBeTrue();
});

test('fulfill deducts inventory and moves pending orders to processing', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product, 2);

    $this->actingAs($admin)
        ->post(route('orders.fulfill', ['order' => $order->order_number]), [
            'note' => 'Start fulfillment.',
        ])
        ->assertRedirect(route('orders.show', ['order' => $order->order_number]));

    expect($order->fresh()->status)->toBe(OrderStatus::Processing)
        ->and($product->fresh()->stock_quantity)->toBe(3);
});

test('refund good stock disposition restores only eligible refunded quantities', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product, 2);
    $orders = app(OrderService::class);

    $orders->fulfillOrder($order, $admin);
    $orders->updateStatus($order->fresh(), OrderStatus::Shipped, $admin);
    $orders->updateStatus($order->fresh(), OrderStatus::Delivered, $admin);

    $refund = app(OrderRefundService::class)->createRefund($order->fresh(), $admin, [
        'amount' => 20.00,
        'reason' => 'Returned',
    ]);

    app(OrderRefundService::class)->markCompleted($refund, $admin, RefundStockDisposition::GoodStock, 'Returned unopened');

    expect($product->fresh()->stock_quantity)->toBe(5)
        ->and($order->items()->first()->refunded_quantity)->toBe(2)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

test('product taxonomy crud and product filters work', function () {
    $admin = User::factory()->admin()->create();
    $tag = ProductTag::factory()->create(['name' => 'Featured', 'slug' => 'featured']);

    $this->actingAs($admin)
        ->post(route('product-management.store', ['module' => 'categories']), [
            'name' => 'Hardware',
            'description' => 'Hardware products',
            'is_active' => true,
        ])
        ->assertRedirect(route('product-management.index'));

    $category = ProductCategory::where('name', 'Hardware')->firstOrFail();

    $product = Product::create([
        'sku' => 'FILTER-001',
        'name' => 'Filtered Product',
        'product_category_id' => $category->id,
        'price' => 10,
        'stock_quantity' => 4,
        'low_stock_threshold' => 2,
        'is_active' => true,
    ]);
    $product->tags()->sync([$tag->id]);

    $this->actingAs($admin)
        ->get(route('products.index', [
            'filters' => [
                'category_id' => $category->id,
                'tag_ids' => [$tag->id],
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Products/index')
            ->has('products.data', 1)
        );

    $cachedReferences = Cache::get(ProductTaxonomyService::REFERENCE_CACHE_KEY);

    expect($cachedReferences['categories'][0])->toBeArray()
        ->and($cachedReferences['categories'][0]['name'])->toBe('Hardware');
});

test('report exports return downloadable spreadsheets', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.export', [
            'type' => 'orders',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('reports honor selected type and date range filters', function () {
    $admin = User::factory()->admin()->create();
    $dateFrom = now()->toDateString();
    $dateTo = now()->toDateString();

    $this->actingAs($admin)
        ->get(route('reports.index', [
            'type' => 'revenue',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/index')
            ->where('filters.type', 'revenue')
            ->where('filters.date_from', $dateFrom)
            ->where('filters.date_to', $dateTo)
            ->where('reports.date_from', $dateFrom)
            ->where('reports.date_to', $dateTo)
            ->has('reports.series.revenue')
        );
});

test('cleanup command records system status activities', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product);

    app(OrderService::class)->fulfillOrder($order, $admin);
    app(OrderService::class)->updateStatus($order->fresh(), OrderStatus::Shipped, $admin);
    app(OrderService::class)->updateStatus($order->fresh(), OrderStatus::Delivered, $admin);
    $order->forceFill(['updated_at' => now()->subDays(8)])->save();

    $this->artisan('orders:cleanup-statuses')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and(OrderActivity::where('order_id', $order->id)->whereNull('actor_id')->where('to_status', OrderStatus::Completed->value)->exists())->toBeTrue();
});
