<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'total_tenants' => Tenant::count(),
                'active_tenants' => Tenant::where('is_active', true)->count(),
                'total_users' => User::count(),
                'total_revenue' => TenantSubscription::where('status', 'active')
                    ->join('plans', 'tenant_subscriptions.plan_id', '=', 'plans.id')
                    ->sum('plans.price'),
            ],
        ]);
    }
}
