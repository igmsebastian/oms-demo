<?php

namespace App\Contracts\Repositories;

use App\Filters\InventoryLogFilter;
use App\Models\InventoryLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryLogRepositoryInterface
{
    public function create(array $data): InventoryLog;

    public function paginate(InventoryLogFilter $filter, int $perPage = 15): LengthAwarePaginator;
}
