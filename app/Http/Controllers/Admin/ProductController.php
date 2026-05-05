<?php

namespace App\Http\Controllers\Admin;

use App\Filters\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\ProductTaxonomyService;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(ProductFilter $filter, ProductService $products, ProductTaxonomyService $taxonomy, ReportService $reports): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('Products/index', [
            'products' => ProductResource::collection($products->getPaginatedProducts($filter)),
            'metrics' => $reports->productMetrics(),
            'references' => $taxonomy->references(),
            'filters' => request()->query('filters', []),
            'sorts' => request()->query('sorts', ['created_at' => 'desc']),
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $products): RedirectResponse
    {
        $products->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created successfully.')]);

        return to_route($request->routeIs('admin.*') ? 'admin.products.index' : 'products.index');
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $products): RedirectResponse
    {
        $products->update($product, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated successfully.')]);

        return to_route($request->routeIs('admin.*') ? 'admin.products.index' : 'products.index');
    }

    public function destroy(Product $product, ProductService $products): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $products->delete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted successfully.')]);

        return to_route(request()->routeIs('admin.*') ? 'admin.products.index' : 'products.index');
    }
}
