<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        $branchesCount = $tenant->branches()->count();
        $activeBranchesCount = $tenant->branches()->where('is_active', true)->count();
        $usersCount = $tenant->users()->count();
        $rolesCount = Role::forTenant($tenant->id)->count();
        $categoriesCount = $tenant->categories()->count();
        $productsCount = $tenant->products()->count();

        $plan = $tenant->subscription?->plan;

        return Inertia::render('tenant/Dashboard', [
            'stats' => [
                'branches_count' => $branchesCount,
                'active_branches_count' => $activeBranchesCount,
                'users_count' => $usersCount,
                'roles_count' => $rolesCount,
                'categories_count' => $categoriesCount,
                'products_count' => $productsCount,
                'plan_name' => $plan?->name ?? 'No Plan',
                'subscription_status' => $tenant->subscription?->status ?? 'none',
                'max_branches' => $plan?->max_branches,
                'max_users' => $plan?->max_users,
                'max_products' => $plan?->max_products,
            ],
        ]);
    }
}
