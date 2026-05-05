<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Exports\OrdersExport;
use App\Exports\RevenueExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewReports');

        $data = $request->validate([
            'type' => ['required', Rule::in(['orders', 'inventory', 'revenue'])],
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
        ]);

        $dateFrom = $data['date_from'] ?? now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $dateTo = $data['date_to'] ?? now()->toDateString();

        $export = match ($data['type']) {
            'inventory' => new InventoryExport($dateFrom, $dateTo),
            'revenue' => new RevenueExport($dateFrom, $dateTo),
            default => new OrdersExport($dateFrom, $dateTo),
        };

        return Excel::download($export, "oms-{$data['type']}-{$dateFrom}-{$dateTo}.xlsx");
    }
}
