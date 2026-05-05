<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(ReportService $reports): ReportResource
    {
        Gate::authorize('viewReports');

        return new ReportResource([
            'orders' => $reports->orderSummary(),
            'revenue' => $reports->revenueSummary(),
            'inventory' => $reports->inventoryStatus(),
            'low_stock_count' => $reports->lowStockProducts(),
        ]);
    }
}
