<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Filters\OrderFilter;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
            ->when($filter->sortParameters() === [], fn ($query) => $query->latest())
            ->paginate($filter->perPage($perPage));
    }

    public function paginateForUser(OrderFilter $filter, User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $filter->apply(Order::with(['items', 'statusReference'])->whereBelongsTo($user))
            ->when($filter->sortParameters() === [], fn ($query) => $query->latest())
            ->paginate($filter->perPage($perPage));
    }

    public function findManyForListing(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Order::query()
            ->with(['user', 'items', 'statusReference'])
            ->whereIn('id', $ids, 'and', false)
            ->get();
    }
}
