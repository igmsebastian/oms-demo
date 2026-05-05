<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Filters\OrderFilter;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function findWithRelations(string $id): ?Order
    {
        return Order::with(['user', 'address', 'items.product', 'activities', 'cancellations', 'refunds'])
            ->find($id);
    }

    public function paginate(OrderFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $filter->apply(Order::with(['user', 'items', 'statusReference']))
            ->latest()
            ->paginate($filter->perPage($perPage));
    }

    public function paginateForUser(OrderFilter $filter, User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $filter->apply(Order::with(['items', 'statusReference'])->whereBelongsTo($user))
            ->latest()
            ->paginate($filter->perPage($perPage));
    }
}
