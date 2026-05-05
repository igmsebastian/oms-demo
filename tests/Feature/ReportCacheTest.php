<?php

use App\Models\User;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\ReportService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    Mail::fake();
});

test('product stock updates invalidate report cache', function () {
    $product = createLifecycleProduct(stock: 5);
    $reports = app(ReportService::class);

    $reports->inventoryStatus();
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeTrue();

    app(ProductService::class)->update($product, ['stock_quantity' => 4]);

    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse();
});

test('order confirmation cancellation and refund completion invalidate report cache', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createLifecycleProduct(stock: 5);
    $order = createLifecycleOrder($user, $product, 1);
    $reports = app(ReportService::class);

    $reports->orderSummary();
    app(OrderService::class)->confirmOrder($order, $admin);
    expect(Cache::has(ReportService::ORDER_SUMMARY_KEY))->toBeFalse();

    $reports->inventoryStatus();
    app(OrderCancellationService::class)->cancelOrder($order->fresh(), $admin, 'Cancelled');
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse();

    $refund = app(OrderRefundService::class)->createRefund($order->fresh(), $admin, ['amount' => 10.00]);
    $reports->revenueSummary();
    app(OrderRefundService::class)->markCompleted($refund, $admin);
    expect(Cache::has(ReportService::REVENUE_SUMMARY_KEY))->toBeFalse();
});
