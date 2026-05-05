<?php

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Filters\ProductFilter;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $products,
        protected ReportService $reports,
    ) {}

    public function create(array $data): Product
    {
        $product = $this->products->create($data);
        $this->reports->invalidate();

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product = $this->products->update($product, $data);
        $this->reports->invalidate();

        return $product;
    }

    public function delete(Product $product): bool
    {
        $deleted = $this->products->delete($product);
        $this->reports->invalidate();

        return $deleted;
    }

    public function getPaginatedProducts(ProductFilter $filter): LengthAwarePaginator
    {
        return $this->products->paginate($filter);
    }
}
