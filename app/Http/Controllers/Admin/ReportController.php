<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(ReportService $reports): Response
    {
        Gate::authorize('viewReports');

        return Inertia::render('admin/reports/index', [
            'reports' => new ReportResource([
                'orders' => $reports->orderSummary(),
                'revenue' => $reports->revenueSummary(),
                'inventory' => $reports->inventoryStatus(),
                'low_stock_count' => $reports->lowStockProducts(),
            ]),
        ]);
    }
}
