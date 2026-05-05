<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Filters\ProductFilter;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function find(string $id): ?Product
    {
        return Product::find($id);
    }

    public function findActive(string $id): ?Product
    {
        return Product::active()->find($id);
    }

    public function paginate(ProductFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $filter->apply(Product::query())
            ->latest()
            ->paginate($filter->perPage($perPage));
    }

    public function findManyForUpdate(array $ids): Collection
    {
        return Product::whereIn('id', $ids)->lockForUpdate()->get();
    }
}
