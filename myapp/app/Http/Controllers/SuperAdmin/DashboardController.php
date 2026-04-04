<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Central\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => $this->dashboardService->getStats(),
            'revenueTrend' => $this->dashboardService->getRevenueTrend(),
            'planDistribution' => $this->dashboardService->getPlanDistribution(),
            'recentActivity' => $this->dashboardService->getRecentActivity(),
            'recentUserLogins' => $this->dashboardService->getRecentUserLogins(),
            'userActivity' => $this->dashboardService->getUserActivityStats(),
            'loginTrend' => $this->dashboardService->getLoginTrend(),
            'pageVisits' => $this->dashboardService->getPageVisitStats(),
            'pageVisitTrend' => $this->dashboardService->getPageVisitTrend(),
            'topReferrers' => $this->dashboardService->getTopReferrers(),
        ]);
    }
}
