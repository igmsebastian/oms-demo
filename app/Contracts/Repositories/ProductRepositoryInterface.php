<?php

namespace App\Contracts\Repositories;

use App\Filters\ProductFilter;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): bool;

    public function find(string $id): ?Product;

    public function findActive(string $id): ?Product;

    public function paginate(ProductFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<int, string>  $ids
     * @return Collection<int, Product>
     */
    public function findManyForUpdate(array $ids): Collection;
}
