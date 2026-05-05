<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\OpenApi\ApiErrorResponses;
use App\Services\ReportService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Reports', 'Read-only report summary endpoints for administrators.')]
class ReportController extends Controller
{
    #[Endpoint(
        operationId: 'reports.index',
        title: 'Get report summaries',
        description: 'Returns order, revenue, inventory, and low stock summary data for API dashboards.',
    )]
    #[Response(status: 200, description: 'Report summary data.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for report query parameters.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
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
