<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ShiftScheduleRequest;
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\TenantUser;
use App\Services\Tenant\ShiftScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftScheduleController extends Controller
{
    public function __construct(
        private ShiftScheduleService $scheduleService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        $schedules = $this->scheduleService->list(
            $tenant,
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('branch_id') ? (int) $request->query('branch_id') : null,
            $request->query('user_id') ? (int) $request->query('user_id') : null,
        );

        $branches = Branch::forTenant($tenant)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $users = $tenant->users()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('tenant/shift-schedules/Index', [
            'schedules' => $schedules,
            'branches' => $branches,
            'users' => $users,
            'filters' => [
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
                'branch_id' => $request->query('branch_id', ''),
                'user_id' => $request->query('user_id', ''),
            ],
        ]);
    }

    public function store(ShiftScheduleRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        // Verify user belongs to tenant
        TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $request->validated('user_id'))
            ->firstOrFail();

        $this->scheduleService->create($tenant, $request->validated(), $request->user()->id);

        return back()->with('success', 'Schedule created successfully.');
    }

    public function update(ShiftScheduleRequest $request, string $tenantSlug, int $schedule): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $scheduleModel = $this->scheduleService->findForTenant($tenant, $schedule);

        // Verify user belongs to tenant
        TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $request->validated('user_id'))
            ->firstOrFail();

        $this->scheduleService->update($scheduleModel, $request->validated());

        return back()->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $schedule): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $scheduleModel = $this->scheduleService->findForTenant($tenant, $schedule);

        $this->scheduleService->delete($scheduleModel);

        return back()->with('success', 'Schedule deleted successfully.');
    }
}
