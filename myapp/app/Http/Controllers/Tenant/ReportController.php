<?php

namespace App\Http\Controllers\Tenant;

use App\Support\BusinessTime;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Services\Tenant\ReportExportService;
use App\Services\Tenant\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportExportService $exportService,
    ) {}

    /** Reports scan every order in range, so a range is capped at a year. */
    private function maxRange(Request $request): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($request) {
            $from = $request->input('date_from');
            if ($from && $value && Carbon::parse($from)->diffInDays(Carbon::parse($value)) > 366) {
                $fail('The date range cannot be longer than one year.');
            }
        };
    }

    public function index(Request $request, string $tenantSlug): Response
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', $this->maxRange($request)],
            'period' => 'nullable|in:daily,weekly,monthly',
            'branch_id' => 'nullable|integer',
        ]);

        $tenant = $request->attributes->get('current_tenant');

        $dateFrom = Carbon::parse($request->input('date_from', BusinessTime::today()->subDays(30)->toDateString()));
        $dateTo = Carbon::parse($request->input('date_to', BusinessTime::today()->toDateString()));

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
            'inventoryReport' => $this->reportService->getInventoryReport($tenant, $dateFrom, $dateTo, $branchId),
            'taxReport' => $this->reportService->getTaxReport($tenant, $dateFrom, $dateTo, $period, $branchId),
            'forecast' => $this->reportService->getForecast($tenant, $dateFrom, $dateTo, $branchId),
            'branches' => $branches,
        ]);
    }

    public function export(Request $request, string $tenantSlug)
    {
        $request->validate([
            'type' => 'required|in:sales_summary,products,payments,operators,branches,inventory,tax',
            'format' => 'required|in:csv,pdf',
            'date_from' => 'nullable|date',
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', $this->maxRange($request)],
            'period' => 'nullable|in:daily,weekly,monthly',
            'branch_id' => 'nullable|integer',
        ]);

        $tenant = $request->attributes->get('current_tenant');

        $dateFrom = Carbon::parse($request->input('date_from', BusinessTime::today()->subDays(30)->toDateString()));
        $dateTo = Carbon::parse($request->input('date_to', BusinessTime::today()->toDateString()));
        $period = $request->input('period', 'daily');
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $type = $request->input('type');
        $format = $request->input('format');

        if ($format === 'csv') {
            return $this->exportService->exportCsv($type, $tenant, $dateFrom, $dateTo, $period, $branchId);
        }

        return $this->exportService->exportPdf($type, $tenant, $dateFrom, $dateTo, $period, $branchId);
    }
}
