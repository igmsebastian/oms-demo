<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;

class ReportRepository
{
    public function orderSummary(): array
    {
        $counts = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status): array => [
                $status->nameValue() => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    public function revenueSummary(): array
    {
        return [
            'gross_revenue' => (float) Order::whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Processing->value,
                OrderStatus::Packed->value,
                OrderStatus::Shipped->value,
                OrderStatus::Delivered->value,
                OrderStatus::Completed->value,
            ])->sum('total_amount'),
            'completed_revenue' => (float) Order::where('status', OrderStatus::Completed->value)->sum('total_amount'),
            'refunded_revenue' => (float) Order::where('status', OrderStatus::Refunded->value)->sum('total_amount'),
        ];
    }

    public function inventoryStatus(): array
    {
        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'inactive_products' => Product::where('is_active', false)->count(),
            'low_stock_products' => Product::lowStock()->count(),
            'out_of_stock_products' => Product::where('stock_quantity', 0)->count(),
        ];
    }

    public function lowStockProductCount(): int
    {
        return Product::lowStock()->count();
    }

    /**
     * @return Collection<int, Product>
     */
    public function lowStockProducts(): Collection
    {
        return Product::lowStock()->orderBy('stock_quantity')->get();
    }
}
