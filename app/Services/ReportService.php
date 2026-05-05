<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    public const ORDER_SUMMARY_KEY = 'reports.orders.summary';

    public const REVENUE_SUMMARY_KEY = 'reports.revenue.summary';

    public const INVENTORY_STATUS_KEY = 'reports.inventory.status';

    public const LOW_STOCK_COUNT_KEY = 'reports.inventory.low_stock_count';

    public const DASHBOARD_PREFIX = 'reports.dashboard';

    public const RANGE_PREFIX = 'reports.range';

    public const PRODUCT_METRICS_PREFIX = 'reports.products.metrics';

    public function __construct(
        protected ReportRepository $reports,
        protected OmsCacheService $cache,
    ) {}

    public function orderSummary(): array
    {
        return Cache::remember(self::ORDER_SUMMARY_KEY, now()->addMinutes(10), fn (): array => $this->reports->orderSummary());
    }

    public function revenueSummary(): array
    {
        return Cache::remember(self::REVENUE_SUMMARY_KEY, now()->addMinutes(10), fn (): array => $this->reports->revenueSummary());
    }

    public function inventoryStatus(): array
    {
        return Cache::remember(self::INVENTORY_STATUS_KEY, now()->addMinutes(10), fn (): array => $this->reports->inventoryStatus());
    }

    public function lowStockProducts(): int
    {
        return Cache::remember(self::LOW_STOCK_COUNT_KEY, now()->addMinutes(10), fn (): int => $this->reports->lowStockProductCount());
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        return $this->cache->remember(
            OmsCacheService::REPORTS_VERSION_KEY,
            self::DASHBOARD_PREFIX,
            [
                'user_id' => $user->id,
                'is_admin' => $user->isAdmin(),
            ],
            now()->addMinutes(10),
            fn (): array => $this->dashboardPayload($user),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$from, $to] = $this->reportRange($dateFrom, $dateTo);

        return $this->cache->remember(
            OmsCacheService::REPORTS_VERSION_KEY,
            self::RANGE_PREFIX,
            [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
            now()->addMinutes(10),
            fn (): array => $this->reportPayload($from, $to),
        );
    }

    /**
     * @return array<string, int>
     */
    public function productMetrics(): array
    {
        return $this->cache->remember(
            OmsCacheService::REPORTS_VERSION_KEY,
            self::PRODUCT_METRICS_PREFIX,
            [],
            now()->addMinutes(10),
            fn (): array => [
                'total_products' => Product::count(),
                'in_stock_products' => Product::where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '>', 'low_stock_threshold')->count(),
                'low_stock_products' => Product::lowStock()->where('stock_quantity', '>', 0)->count(),
                'no_stock_products' => Product::where('stock_quantity', 0)->count(),
            ],
        );
    }

    public function invalidate(): void
    {
        Cache::forget(self::ORDER_SUMMARY_KEY);
        Cache::forget(self::REVENUE_SUMMARY_KEY);
        Cache::forget(self::INVENTORY_STATUS_KEY);
        Cache::forget(self::LOW_STOCK_COUNT_KEY);

        $this->cache->invalidateReports();
    }

    /**
     * @return array<string, mixed>
     */
    protected function dashboardPayload(User $user): array
    {
        $orders = Order::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->whereBelongsTo($user))
            ->get(['id', 'status', 'total_amount', 'created_at']);

        $statusCounts = collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [
            $status->nameValue() => $orders->where('status', $status)->count(),
        ]);

        $revenueSeries = $orders
            ->groupBy(fn (Order $order): string => $order->created_at?->format('Y-m') ?? now()->format('Y-m'))
            ->sortKeys()
            ->map(fn ($orders, string $period): array => [
                'period' => $period,
                'revenue' => round((float) $orders->sum('total_amount'), 2),
                'orders' => $orders->count(),
            ])
            ->values()
            ->all();

        return [
            'kpis' => [
                'total_orders' => $orders->count(),
                'pending_orders' => (int) $statusCounts->get('pending', 0),
                'revenue' => round((float) $orders->sum('total_amount'), 2),
                'low_stock_products' => $user->isAdmin() ? $this->lowStockProducts() : 0,
            ],
            'status_counts' => $statusCounts->all(),
            'status_chart' => collect(OrderStatus::cases())->map(fn (OrderStatus $status): array => [
                'name' => $status->label(),
                'value' => (int) $statusCounts->get($status->nameValue(), 0),
            ])->values()->all(),
            'revenue_series' => $revenueSeries,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function reportPayload(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'status', 'total_amount', 'created_at']);

        return [
            'orders' => [
                'total' => $orders->count(),
                'pending' => $orders->where('status', OrderStatus::Pending)->count(),
                'completed' => $orders->where('status', OrderStatus::Completed)->count(),
                'cancelled' => $orders->where('status', OrderStatus::Cancelled)->count(),
            ],
            'inventory' => $this->inventoryStatus(),
            'low_stock_count' => $this->lowStockProducts(),
            'revenue' => [
                'gross_revenue' => round((float) $orders->sum('total_amount'), 2),
                'completed_revenue' => round((float) $orders->where('status', OrderStatus::Completed)->sum('total_amount'), 2),
                'refunded_revenue' => round((float) $orders->where('status', OrderStatus::Refunded)->sum('total_amount'), 2),
            ],
            'series' => [
                'revenue' => $orders
                    ->groupBy(fn (Order $order): string => $order->created_at?->format('Y-m-d') ?? now()->toDateString())
                    ->sortKeys()
                    ->map(fn ($orders, string $date): array => [
                        'date' => $date,
                        'revenue' => round((float) $orders->sum('total_amount'), 2),
                        'orders' => $orders->count(),
                    ])
                    ->values()
                    ->all(),
                'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $status): array => [
                    'name' => $status->label(),
                    'value' => $orders->where('status', $status)->count(),
                ])->values()->all(),
            ],
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function reportRange(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            $dateFrom ? CarbonImmutable::parse($dateFrom)->startOfDay() : CarbonImmutable::now()->subMonthNoOverflow()->startOfMonth(),
            $dateTo ? CarbonImmutable::parse($dateTo)->endOfDay() : CarbonImmutable::now()->endOfDay(),
        ];
    }
}
