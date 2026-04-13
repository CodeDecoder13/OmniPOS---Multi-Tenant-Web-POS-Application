<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\AIInsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AIInsightsController extends Controller
{
    public function __construct(private readonly AIInsightsService $service) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/ai-insights/Index', [
            'summary' => $this->service->getSummary($tenant),
            'forecast' => $this->service->getForecast($tenant),
            'peakHours' => $this->service->getPeakHours($tenant),
            'productTrends' => $this->service->getProductTrends($tenant),
            'insights' => $this->service->generateInsights($tenant),
        ]);
    }
}
