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
        protected OmsCacheService $cache,
    ) {}

    public function create(array $data): Product
    {
        $product = $this->products->create($data);
        $this->invalidateProductReads();

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product = $this->products->update($product, $data);
        $this->invalidateProductReads();

        return $product;
    }

    public function delete(Product $product): bool
    {
        $deleted = $this->products->delete($product);
        $this->invalidateProductReads();

        return $deleted;
    }

    public function getPaginatedProducts(ProductFilter $filter): LengthAwarePaginator
    {
        $payload = $this->cache->remember(
            OmsCacheService::PRODUCTS_VERSION_KEY,
            'products.index',
            $filter->cacheFingerprint(15),
            now()->addMinutes(5),
            fn (): array => $this->cache->paginatorPayload($this->products->paginate($filter)),
        );

        return $this->cache->restorePaginator(
            $payload,
            $this->products->findManyForListing($payload['ids'] ?? []),
        );
    }

    protected function invalidateProductReads(): void
    {
        $this->reports->invalidate();
        $this->cache->invalidateProducts();
    }
}
