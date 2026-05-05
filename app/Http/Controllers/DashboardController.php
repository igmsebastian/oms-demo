<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        return Inertia::render('Dashboard/index', [
            'dashboard' => $reports->dashboard($request->user()),
        ]);
    }
}
