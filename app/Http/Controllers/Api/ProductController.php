<?php

namespace App\Http\Controllers\Api;

use App\Filters\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\OpenApi\ApiErrorResponses;
use App\Services\ProductService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Products', 'Inventory product endpoints for listing, creating, updating, and deleting products.')]
class ProductController extends Controller
{
    #[Endpoint(
        operationId: 'products.index',
        title: 'List products',
        description: 'Returns a paginated inventory product list. Supports filters and sorting through query parameters.',
    )]
    #[Response(status: 200, description: 'Paginated product list.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for supplied query parameters.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function index(ProductFilter $filter, ProductService $products): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Product::class);

        return ProductResource::collection($products->getPaginatedProducts($filter));
    }

    #[Endpoint(
        operationId: 'products.store',
        title: 'Create product',
        description: 'Creates an inventory product with pricing, stock, taxonomy references, and tags.',
    )]
    #[Response(status: 200, description: 'Product created successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for product data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function store(StoreProductRequest $request, ProductService $products): ProductResource
    {
        return new ProductResource($products->create($request->validated()));
    }

    #[Endpoint(
        operationId: 'products.show',
        title: 'Show product',
        description: 'Returns one product with taxonomy and tag references.',
    )]
    #[Response(status: 200, description: 'Product details.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for product lookup data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load(['category', 'brand', 'unit', 'size', 'color', 'tags']));
    }

    #[Endpoint(
        operationId: 'products.update',
        title: 'Update product',
        description: 'Updates product fields, stock values, taxonomy references, and tags.',
        method: 'PATCH',
    )]
    #[Response(status: 200, description: 'Product updated successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for product update data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function update(UpdateProductRequest $request, Product $product, ProductService $products): ProductResource
    {
        return new ProductResource($products->update($product, $request->validated()));
    }

    #[Endpoint(
        operationId: 'products.destroy',
        title: 'Delete product',
        description: 'Soft deletes an inventory product. Historical order item snapshots remain intact.',
    )]
    #[Response(status: 204, description: 'Product deleted successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for product deletion data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function destroy(Product $product, ProductService $products): JsonResponse
    {
        Gate::authorize('delete', $product);

        $products->delete($product);

        return response()->json(status: 204);
    }
}
