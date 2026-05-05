<?php

use App\Enums\InventoryChangeType;
use App\Models\InventoryLog;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ReportService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('deduct stock updates inventory creates logs stores context and invalidates caches', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 5, 'low_stock_threshold' => 1]);
    $order = createOmsOrder($user, [['product' => $product, 'quantity' => 2]]);
    $item = $order->items()->first();

    app(ReportService::class)->inventoryStatus();
    expect(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeTrue();

    app(InventoryService::class)->deductStock($product, 2, [
        'order' => $order,
        'order_item' => $item,
        'actor' => $admin,
        'reason' => 'Reserve stock',
        'metadata' => ['channel' => 'test'],
    ]);

    $log = InventoryLog::firstOrFail();

    expect($product->fresh()->stock_quantity)->toBe(3)
        ->and($log->change_type)->toBe(InventoryChangeType::Deduction)
        ->and($log->quantity_change)->toBe(-2)
        ->and($log->stock_before)->toBe(5)
        ->and($log->stock_after)->toBe(3)
        ->and($log->changed_by_user_id)->toBe($admin->id)
        ->and($log->order_id)->toBe($order->id)
        ->and($log->order_item_id)->toBe($item->id)
        ->and($log->metadata)->toBe(['channel' => 'test'])
        ->and(Cache::has(ReportService::INVENTORY_STATUS_KEY))->toBeFalse();
});

test('deduct stock prevents negative stock and rolls back the product quantity', function () {
    $admin = User::factory()->admin()->create();
    $product = createOmsProduct(['stock_quantity' => 1]);

    expect(fn () => app(InventoryService::class)->deductStock($product, 2, ['actor' => $admin]))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->stock_quantity)->toBe(1)
        ->and(InventoryLog::count())->toBe(0);
});

test('restore stock and manual adjustment create correct logs and validation errors', function () {
    $admin = User::factory()->admin()->create();
    $product = createOmsProduct(['stock_quantity' => 4]);

    app(InventoryService::class)->restoreStock($product, 3, [
        'actor' => $admin,
        'reason' => 'Returned to shelf',
    ]);

    app(InventoryService::class)->adjustStock($product->fresh(), -2, 'Cycle count correction', $admin);

    expect($product->fresh()->stock_quantity)->toBe(5)
        ->and(InventoryLog::where('change_type', InventoryChangeType::Restore->value)->first()?->quantity_change)->toBe(3)
        ->and(InventoryLog::where('change_type', InventoryChangeType::Adjustment->value)->first()?->quantity_change)->toBe(-2);

    expect(fn () => app(InventoryService::class)->restoreStock($product->fresh(), 0, ['actor' => $admin]))
        ->toThrow(ValidationException::class);

    expect(fn () => app(InventoryService::class)->adjustStock($product->fresh(), -999, 'Bad count', $admin))
        ->toThrow(ValidationException::class);
});
