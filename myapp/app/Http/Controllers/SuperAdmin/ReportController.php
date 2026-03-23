<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Central\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    public function revenue(Request $request): Response
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(11)->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->toDateString());

        return Inertia::render('admin/reports/Revenue', [
            'report' => $this->reportService->revenueReport($dateFrom, $dateTo),
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }

    public function tenants(Request $request): Response
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(11)->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->toDateString());

        return Inertia::render('admin/reports/Tenants', [
            'report' => $this->reportService->tenantReport($dateFrom, $dateTo),
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }

    public function users(Request $request): Response
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(11)->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->toDateString());

        return Inertia::render('admin/reports/Users', [
            'report' => $this->reportService->userReport($dateFrom, $dateTo),
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }

    public function subscriptions(): Response
    {
        return Inertia::render('admin/reports/Subscriptions', [
            'report' => $this->reportService->subscriptionReport(),
        ]);
    }
}
