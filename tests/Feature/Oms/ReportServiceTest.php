<?php

use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('report summaries respect status and date range data', function () {
    $user = User::factory()->create();
    $product = createOmsProduct();
    $inside = createOmsOrder($user, [['product' => $product]]);
    $inside->forceFill([
        'status' => OrderStatus::Completed,
        'total_amount' => 200,
        'created_at' => now()->subDays(2),
    ])->save();
    $outside = createOmsOrder($user, [['product' => $product]]);
    $outside->forceFill([
        'status' => OrderStatus::Cancelled,
        'total_amount' => 99,
        'created_at' => now()->subMonths(3),
    ])->save();

    $reports = app(ReportService::class)->reports(now()->subWeek()->toDateString(), now()->toDateString());

    expect($reports['orders']['total'])->toBe(1)
        ->and($reports['orders']['completed'])->toBe(1)
        ->and($reports['orders']['cancelled'])->toBe(0)
        ->and($reports['revenue']['gross_revenue'])->toBe(200.0)
        ->and($reports['revenue']['completed_revenue'])->toBe(200.0)
        ->and($reports['series']['revenue'][0]['orders'])->toBe(1);
});

test('report service caches and returns inventory status and dashboard series', function () {
    $admin = User::factory()->admin()->create();
    createOmsProduct(['stock_quantity' => 10, 'low_stock_threshold' => 3]);
    createOmsProduct(['stock_quantity' => 1, 'low_stock_threshold' => 3]);
    createOmsProduct(['stock_quantity' => 0, 'low_stock_threshold' => 3]);

    $inventory = app(ReportService::class)->inventoryStatus();
    $dashboard = app(ReportService::class)->dashboard($admin);

    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeTrue()
        ->and($inventory['total_products'])->toBe(3)
        ->and($inventory['active_products'])->toBe(3)
        ->and($inventory['low_stock_products'])->toBe(2)
        ->and($dashboard['kpis'])->toHaveKeys(['total_orders', 'pending_orders', 'revenue', 'low_stock_products'])
        ->and($dashboard['status_chart'])->toHaveCount(count(OrderStatus::cases()));
});

test('report page validates invalid date ranges', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
            'type' => 'revenue',
        ]))
        ->assertSessionHasErrors('date_to');
});
