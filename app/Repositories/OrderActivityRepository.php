<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderActivityRepositoryInterface;
use App\Filters\OrderActivityFilter;
use App\Models\OrderActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderActivityRepository implements OrderActivityRepositoryInterface
{
    public function create(array $data): OrderActivity
    {
        return OrderActivity::create($data);
    }

    public function paginate(OrderActivityFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $filter->apply(OrderActivity::with(['order', 'actor']))
            ->latest()
            ->paginate($filter->perPage($perPage));
    }
}
