<?php

use App\Enums\InventoryChangeType;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderCancellation;
use App\Models\OrderRefund;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductTag;
use App\Models\ProductUnit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('demo seeders create realistic sneaker catalog and order lifecycle data', function () {
    User::factory()->admin()->create(['email' => 'admin@example.com']);
    collect([
        'customer@example.com',
        'noah.bennett@example.com',
    ])->each(fn (string $email): User => User::factory()->create(['email' => $email]));

    $this->seed(DatabaseSeeder::class);

    expect(User::where('role', UserRole::Admin->value)->count())->toBeGreaterThanOrEqual(3)
        ->and(User::where('role', UserRole::User->value)->count())->toBeGreaterThanOrEqual(6)
        ->and(User::where('email', 'basty@mydemo.com')->where('role', UserRole::Admin->value)->exists())->toBeTrue()
        ->and(User::where('email', 'like', '%@example.com')->exists())->toBeFalse()
        ->and(User::where('email', 'not like', '%@mydemo.com')->exists())->toBeFalse()
        ->and(ProductCategory::count())->toBeGreaterThanOrEqual(6)
        ->and(ProductBrand::count())->toBeGreaterThanOrEqual(8)
        ->and(ProductUnit::count())->toBeGreaterThanOrEqual(2)
        ->and(ProductSize::count())->toBeGreaterThanOrEqual(6)
        ->and(ProductColor::count())->toBeGreaterThanOrEqual(8)
        ->and(ProductTag::count())->toBeGreaterThanOrEqual(9);

    $demoProducts = Product::where('sku', 'like', 'SNEAK-%');

    expect((clone $demoProducts)->count())->toBeGreaterThanOrEqual(18)
        ->and((clone $demoProducts)->whereColumn('stock_quantity', '>', 'low_stock_threshold')->exists())->toBeTrue()
        ->and((clone $demoProducts)->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->exists())->toBeTrue()
        ->and((clone $demoProducts)->where('stock_quantity', 0)->exists())->toBeTrue()
        ->and((clone $demoProducts)->whereDoesntHave('tags')->exists())->toBeFalse()
        ->and((clone $demoProducts)->where(function ($query): void {
            $query->whereNull('product_category_id')
                ->orWhereNull('product_brand_id')
                ->orWhereNull('product_unit_id')
                ->orWhereNull('product_size_id')
                ->orWhereNull('product_color_id');
        })->exists())->toBeFalse();

    $orders = Order::with(['items', 'activities', 'cancellations', 'refunds'])
        ->where('order_number', 'like', 'OMS-DEMO-%')
        ->get();
    $seededStatuses = $orders
        ->pluck('status')
        ->map(fn (OrderStatus $status): int => $status->value)
        ->all();
    $expectedStatuses = collect(OrderStatus::cases())
        ->map(fn (OrderStatus $status): int => $status->value)
        ->all();
    $oldestOrderDate = Carbon::parse(
        Order::where('order_number', 'like', 'OMS-DEMO-%')->min('created_at')
    );
    $latestOrderDate = Carbon::parse(
        Order::where('order_number', 'like', 'OMS-DEMO-%')->max('created_at')
    );
    $currentMonthOrderCount = Order::where('order_number', 'like', 'OMS-DEMO-%')
        ->whereYear('created_at', now()->year)
        ->whereMonth('created_at', now()->month)
        ->count();
    $todayAndYesterdayOrderCount = Order::where('order_number', 'like', 'OMS-DEMO-%')
        ->where(function ($query): void {
            $query->whereDate('created_at', now()->toDateString())
                ->orWhereDate('created_at', now()->subDay()->toDateString());
        })
        ->count();
    $statusesWithoutRemarks = collect(OrderStatus::cases())
        ->filter(fn (OrderStatus $status): bool => ! $orders->contains(
            fn (Order $order): bool => $order->status === $status
                && $order->activities->contains(
                    fn ($activity): bool => $activity->event === OrderActivityEvent::RemarkAdded,
                ),
        ));
    $cancellationOrdersWithoutRemarks = $orders
        ->filter(fn (Order $order): bool => in_array($order->status, [
            OrderStatus::CancellationRequested,
            OrderStatus::PartiallyCancelled,
            OrderStatus::Cancelled,
        ], true))
        ->reject(fn (Order $order): bool => $order->activities->contains(
            fn ($activity): bool => $activity->event === OrderActivityEvent::RemarkAdded,
        ));
    $futureActivityCount = OrderActivity::where('created_at', '>', now())->count();
    $admin = User::where('email', 'basty@mydemo.com')->firstOrFail();

    expect($orders->count())->toBeGreaterThanOrEqual(1000)
        ->and(array_diff($expectedStatuses, $seededStatuses))->toBeEmpty()
        ->and($oldestOrderDate->toDateString())->toBe(now()->subYears(2)->toDateString())
        ->and($latestOrderDate->toDateString())->toBe(now()->toDateString())
        ->and($currentMonthOrderCount)->toBeGreaterThan(0)
        ->and($todayAndYesterdayOrderCount)->toBeGreaterThan($orders->count() / 2)
        ->and($statusesWithoutRemarks)->toBeEmpty()
        ->and($cancellationOrdersWithoutRemarks)->toBeEmpty()
        ->and($futureActivityCount)->toBe(0)
        ->and($orders->every(fn (Order $order): bool => $order->items->isNotEmpty()))->toBeTrue()
        ->and($orders->every(fn (Order $order): bool => $order->activities->contains(
            fn ($activity): bool => $activity->event === OrderActivityEvent::OrderCreated,
        )))->toBeTrue()
        ->and($orders->whereNotNull('cancellation_reason')->count())->toBeGreaterThanOrEqual(3)
        ->and(OrderCancellation::count())->toBeGreaterThanOrEqual(3)
        ->and(OrderRefund::count())->toBeGreaterThanOrEqual(2)
        ->and(InventoryLog::whereNotNull('order_id')->where('change_type', InventoryChangeType::Deduction->value)->exists())->toBeTrue()
        ->and(InventoryLog::whereNotNull('order_id')->where('change_type', InventoryChangeType::Restore->value)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('orders.show', ['order' => 'OMS-DEMO-TODAY-34']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderDetails/index')
            ->where('order.order_number', 'OMS-DEMO-TODAY-34')
            ->has('order.allowed_actions')
            ->has('order.available_statuses')
        );
});
