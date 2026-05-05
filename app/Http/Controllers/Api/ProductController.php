<?php

namespace App\Http\Controllers\Api;

use App\Filters\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(ProductFilter $filter, ProductService $products): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Product::class);

        return ProductResource::collection($products->getPaginatedProducts($filter));
    }

    public function store(StoreProductRequest $request, ProductService $products): ProductResource
    {
        return new ProductResource($products->create($request->validated()));
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $products): ProductResource
    {
        return new ProductResource($products->update($product, $request->validated()));
    }

    public function destroy(Product $product, ProductService $products): JsonResponse
    {
        Gate::authorize('delete', $product);

        $products->delete($product);

        return response()->json(status: 204);
    }
}
