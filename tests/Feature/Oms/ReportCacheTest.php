<?php

use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OmsCacheService;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\ReportService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('product and inventory mutations invalidate inventory and low stock report caches', function () {
    $admin = User::factory()->admin()->create();
    $product = createOmsProduct(['stock_quantity' => 5]);
    $reports = app(ReportService::class);

    $reports->inventoryStatus();
    $reports->lowStockProducts();
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeTrue()
        ->and(Cache::has(ReportService::LOW_STOCK_COUNT_KEY))->toBeTrue();

    app(ProductService::class)->update($product, ['stock_quantity' => 4]);
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse()
        ->and(Cache::has(ReportService::LOW_STOCK_COUNT_KEY))->toBeFalse();

    $reports->inventoryStatus();
    app(InventoryService::class)->adjustStock($product->fresh(), -1, 'Cycle count', $admin);
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse();
});

test('order creation fulfillment cancellation and refund completion invalidate report caches', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 5]);
    $reports = app(ReportService::class);

    $reports->orderSummary();
    $order = createOmsOrder($user, [['product' => $product]]);
    expect(Cache::has(ReportService::ORDER_SUMMARY_KEY))->toBeFalse();

    $reports->revenueSummary();
    $reports->inventoryStatus();
    app(OrderService::class)->fulfillOrder($order, $admin);
    expect(Cache::has(ReportService::REVENUE_SUMMARY_KEY))->toBeFalse()
        ->and(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse();

    $reports->orderSummary();
    app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Cancelled');
    expect(Cache::has(ReportService::ORDER_SUMMARY_KEY))->toBeFalse();

    $refund = app(OrderRefundService::class)->createRefund($order->fresh(), $admin, ['amount' => 120.00]);
    $reports->revenueSummary();
    app(OrderRefundService::class)->markCompleted($refund, $admin);
    expect(Cache::has(ReportService::REVENUE_SUMMARY_KEY))->toBeFalse()
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

test('date range reports use versioned cache and refresh after order writes', function () {
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 10]);
    $reports = app(ReportService::class);
    $cache = app(OmsCacheService::class);
    $dateFrom = now()->subWeek()->toDateString();
    $dateTo = now()->toDateString();

    createOmsOrder($user, [['product' => $product]]);

    $first = $reports->reports($dateFrom, $dateTo);
    $firstKey = $cache->key(OmsCacheService::REPORTS_VERSION_KEY, ReportService::RANGE_PREFIX, [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);

    expect($first['orders']['total'])->toBe(1)
        ->and(Cache::has($firstKey))->toBeTrue();

    createOmsOrder($user, [['product' => $product]]);

    $secondKey = $cache->key(OmsCacheService::REPORTS_VERSION_KEY, ReportService::RANGE_PREFIX, [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);
    $second = $reports->reports($dateFrom, $dateTo);

    expect($secondKey)->not->toBe($firstKey)
        ->and($second['orders']['total'])->toBe(2)
        ->and(Cache::has($secondKey))->toBeTrue();
});

test('product and order index caches are versioned and refreshed after writes', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 10]);
    $cache = app(OmsCacheService::class);
    $fingerprint = [
        'filters' => [],
        'sorts' => [],
        'page' => 1,
        'per_page' => 15,
        'simple' => false,
    ];

    createOmsOrder($user, [['product' => $product]]);

    $this->actingAs($admin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products.data', 1));

    $productCacheKey = $cache->key(OmsCacheService::PRODUCTS_VERSION_KEY, 'products.index', $fingerprint);
    expect(Cache::has($productCacheKey))->toBeTrue();

    app(ProductService::class)->create([
        'sku' => 'CACHE-PRODUCT-002',
        'name' => 'Cache Test Sneaker',
        'price' => 150,
        'stock_quantity' => 4,
        'low_stock_threshold' => 2,
    ]);

    $newProductCacheKey = $cache->key(OmsCacheService::PRODUCTS_VERSION_KEY, 'products.index', $fingerprint);

    $this->actingAs($admin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products.data', 2));

    $this->actingAs($admin)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('orders.data', 1));

    $orderCacheKey = $cache->key(OmsCacheService::ORDERS_VERSION_KEY, 'orders.index.admin', $fingerprint);
    expect(Cache::has($orderCacheKey))->toBeTrue();

    createOmsOrder($user, [['product' => $product]]);

    $newOrderCacheKey = $cache->key(OmsCacheService::ORDERS_VERSION_KEY, 'orders.index.admin', $fingerprint);

    $this->actingAs($admin)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('orders.data', 2));

    expect($newProductCacheKey)->not->toBe($productCacheKey)
        ->and($newOrderCacheKey)->not->toBe($orderCacheKey)
        ->and(Cache::has($newProductCacheKey))->toBeTrue()
        ->and(Cache::has($newOrderCacheKey))->toBeTrue();
});
