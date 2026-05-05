<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Filters\ProductFilter;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ProductRepository implements ProductRepositoryInterface
{
    public function create(array $data): Product
    {
        $tagIds = Arr::pull($data, 'tag_ids', []);
        $product = Product::create($data);
        $product->tags()->sync($tagIds);

        return $product->load(['category', 'brand', 'unit', 'size', 'color', 'tags']);
    }

    public function update(Product $product, array $data): Product
    {
        $tagIds = Arr::pull($data, 'tag_ids', null);
        $product->update($data);

        if (is_array($tagIds)) {
            $product->tags()->sync($tagIds);
        }

        return $product->refresh()->load(['category', 'brand', 'unit', 'size', 'color', 'tags']);
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
        return $filter->apply(Product::query()->with(['category', 'brand', 'unit', 'size', 'color', 'tags']))
            ->when($filter->sortParameters() === [], fn ($query) => $query->latest())
            ->paginate($filter->perPage($perPage));
    }

    public function findManyForListing(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Product::query()
            ->with(['category', 'brand', 'unit', 'size', 'color', 'tags'])
            ->whereIn('id', $ids, 'and', false)
            ->get();
    }

    public function findManyForUpdate(array $ids): Collection
    {
        return Product::whereIn('id', $ids, 'and', false)->lockForUpdate()->get();
    }
}
