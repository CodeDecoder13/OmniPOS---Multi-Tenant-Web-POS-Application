<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Services\Tenant\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'period' => 'nullable|in:daily,weekly,monthly',
            'branch_id' => 'nullable|integer',
        ]);

        $tenant = $request->attributes->get('current_tenant');

        $dateFrom = Carbon::parse($request->input('date_from', now()->subDays(30)->toDateString()));
        $dateTo = Carbon::parse($request->input('date_to', now()->toDateString()));

        $period = $request->input('period', 'daily');
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;

        $branches = Branch::forTenant($tenant)->where('is_active', true)
            ->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('tenant/reports/Index', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'period' => $period,
                'branch_id' => $branchId,
            ],
            'summary' => $this->reportService->getSummary($tenant, $dateFrom, $dateTo, $branchId),
            'salesTrend' => $this->reportService->getSalesTrend($tenant, $dateFrom, $dateTo, $period, $branchId),
            'topProducts' => $this->reportService->getTopProducts($tenant, $dateFrom, $dateTo, $branchId),
            'paymentBreakdown' => $this->reportService->getPaymentBreakdown($tenant, $dateFrom, $dateTo, $branchId),
            'operatorPerformance' => $this->reportService->getOperatorPerformance($tenant, $dateFrom, $dateTo, $branchId),
            'branchComparison' => $this->reportService->getBranchComparison($tenant, $dateFrom, $dateTo),
            'branches' => $branches,
        ]);
    }
}
