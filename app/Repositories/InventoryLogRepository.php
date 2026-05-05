<?php

namespace App\Repositories;

use App\Contracts\Repositories\InventoryLogRepositoryInterface;
use App\Filters\InventoryLogFilter;
use App\Models\InventoryLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryLogRepository implements InventoryLogRepositoryInterface
{
    public function create(array $data): InventoryLog
    {
        return InventoryLog::create($data);
    }

    public function paginate(InventoryLogFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $filter->apply(InventoryLog::with(['product', 'order', 'orderItem', 'changedBy']))
            ->latest()
            ->paginate($filter->perPage($perPage));
    }
}
