<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    public const ORDER_SUMMARY_KEY = 'reports.orders.summary';

    public const REVENUE_SUMMARY_KEY = 'reports.revenue.summary';

    public const INVENTORY_STATUS_KEY = 'reports.inventory.status';

    public const LOW_STOCK_COUNT_KEY = 'reports.inventory.low_stock_count';

    public function __construct(
        protected ReportRepository $reports,
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

    public function invalidate(): void
    {
        Cache::forget(self::ORDER_SUMMARY_KEY);
        Cache::forget(self::REVENUE_SUMMARY_KEY);
        Cache::forget(self::INVENTORY_STATUS_KEY);
        Cache::forget(self::LOW_STOCK_COUNT_KEY);
    }
}
