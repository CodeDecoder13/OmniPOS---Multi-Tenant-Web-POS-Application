<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = Tenant::with(['owner', 'subscription.plan'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function show(string $id): Response
    {
        $tenant = Tenant::with(['owner', 'subscription.plan', 'users'])
            ->findOrFail($id);

        return Inertia::render('admin/tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function toggle(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => ! $tenant->is_active]);

        return back()->with('success', "Tenant {$tenant->name} has been " . ($tenant->is_active ? 'activated' : 'deactivated') . '.');
    }
}
