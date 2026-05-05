<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        Gate::authorize('viewReports');

        $data = $request->validate([
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'type' => ['nullable', Rule::in(['orders', 'inventory', 'revenue'])],
        ]);

        return Inertia::render('Reports/index', [
            'reports' => (new ReportResource($reports->reports(
                $data['date_from'] ?? null,
                $data['date_to'] ?? null,
            )))->resolve(),
            'filters' => [
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'type' => $data['type'] ?? 'orders',
            ],
        ]);
    }
}
