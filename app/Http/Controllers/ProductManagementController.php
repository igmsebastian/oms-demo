<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductTaxonomyRequest;
use App\Http\Requests\UpdateProductTaxonomyRequest;
use App\Services\ProductTaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductManagementController extends Controller
{
    public function index(Request $request, ProductTaxonomyService $taxonomy): Response
    {
        Gate::authorize('viewReports');

        return Inertia::render('ProductManagement/index', [
            'modules' => collect($taxonomy->modules())->keys()->mapWithKeys(fn (string $module): array => [
                $module => [
                    'name' => $module,
                    'label' => match ($module) {
                        'units' => 'Unit of Measure',
                        default => str($module)->replace('_', ' ')->headline()->toString(),
                    },
                    'records' => $taxonomy->listPayload($module, $request->string('keyword')->toString()),
                ],
            ])->all(),
            'filters' => $request->only('keyword'),
        ]);
    }

    public function store(StoreProductTaxonomyRequest $request, string $module, ProductTaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->create($module, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reference record created successfully.')]);

        return to_route('product-management.index');
    }

    public function update(UpdateProductTaxonomyRequest $request, string $module, string $record, ProductTaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->update($module, $record, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reference record updated successfully.')]);

        return to_route('product-management.index');
    }

    public function destroy(Request $request, string $module, string $record, ProductTaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('viewReports');

        $taxonomy->delete($module, $record);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reference record deleted successfully.')]);

        return to_route('product-management.index');
    }
}
