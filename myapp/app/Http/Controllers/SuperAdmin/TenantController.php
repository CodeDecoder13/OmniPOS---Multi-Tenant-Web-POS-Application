<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\BusinessType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\TenantRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Central\AdminActivityLogService;
use App\Services\Central\TenantActivityService;
use App\Services\Central\TenantService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService,
        private AdminActivityLogService $activityLog,
        private TenantActivityService $tenantActivity,
    ) {}

    public function index(Request $request): Response
    {
        $tenants = $this->tenantService->list($request->only(['search', 'is_active', 'business_type', 'plan_id', 'sort', 'direction']));

        return Inertia::render('admin/tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'is_active', 'business_type', 'plan_id']),
            'plans' => Plan::all(['id', 'name']),
            'businessTypes' => BusinessType::options(),
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

    public function create(): Response
    {
        return Inertia::render('admin/tenants/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'plans' => Plan::active()->get(['id', 'name']),
            'businessTypes' => BusinessType::options(),
        ]);
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'created_tenant',
            $tenant,
            ['name' => $tenant->name],
            $request->ip(),
        );

        return redirect()->route('admin.tenants.index')->with('success', "Tenant {$tenant->name} created successfully.");
    }

    public function edit(string $id): Response
    {
        $tenant = Tenant::with(['subscription'])->findOrFail($id);

        return Inertia::render('admin/tenants/Edit', [
            'tenant' => $tenant,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'plans' => Plan::active()->get(['id', 'name']),
            'businessTypes' => BusinessType::options(),
        ]);
    }

    public function update(TenantRequest $request, string $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant = $this->tenantService->update($tenant, $request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'updated_tenant',
            $tenant,
            ['name' => $tenant->name],
            $request->ip(),
        );

        return redirect()->route('admin.tenants.index')->with('success', "Tenant {$tenant->name} updated successfully.");
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);
        $name = $tenant->name;

        $this->tenantService->delete($tenant);

        $this->activityLog->log(
            $request->user('admin'),
            'deleted_tenant',
            null,
            ['name' => $name],
            $request->ip(),
        );

        return redirect()->route('admin.tenants.index')->with('success', "Tenant {$name} deleted successfully.");
    }

    public function activity(Request $request, string $id): Response
    {
        $tenant = Tenant::findOrFail($id);

        $filters = $request->only(['user_id', 'event_type', 'date_from', 'date_to']);

        return Inertia::render('admin/tenants/Activity', [
            'tenant' => $tenant,
            'stats' => $this->tenantActivity->getSummaryStats($id),
            'timeline' => $this->tenantActivity->getTimeline($id, $filters),
            'filters' => $filters,
            'users' => $this->tenantActivity->getTenantUsers($id),
            'eventTypes' => [
                ['value' => 'login', 'label' => 'Login'],
                ['value' => 'activity', 'label' => 'Activity'],
                ['value' => 'shift_open', 'label' => 'Shift Open'],
                ['value' => 'shift_close', 'label' => 'Shift Close'],
                ['value' => 'order', 'label' => 'Order'],
                ['value' => 'product_created', 'label' => 'Product Created'],
            ],
        ]);
    }

    public function toggle(Request $request, string $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);
        $this->tenantService->toggle($tenant);

        $this->activityLog->log(
            $request->user('admin'),
            $tenant->is_active ? 'activated_tenant' : 'deactivated_tenant',
            $tenant,
            ['name' => $tenant->name],
            $request->ip(),
        );

        return back()->with('success', "Tenant {$tenant->name} has been " . ($tenant->is_active ? 'activated' : 'deactivated') . '.');
    }
}
