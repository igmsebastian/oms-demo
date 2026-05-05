<?php

namespace App\Contracts\Repositories;

use App\Filters\OrderFilter;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;

    public function findWithRelations(string $id): ?Order;

    public function paginate(OrderFilter $filter, int $perPage = 15): LengthAwarePaginator;

    public function paginateForUser(OrderFilter $filter, User $user, int $perPage = 15): LengthAwarePaginator;
}
