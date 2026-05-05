<?php

namespace App\Contracts\Repositories;

use App\Filters\OrderActivityFilter;
use App\Models\OrderActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderActivityRepositoryInterface
{
    public function create(array $data): OrderActivity;

    public function paginate(OrderActivityFilter $filter, int $perPage = 15): LengthAwarePaginator;
}
