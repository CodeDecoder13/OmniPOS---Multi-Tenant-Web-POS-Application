<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffRequest;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\StaffMember;
use App\Services\Tenant\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The people who do the work on a sale (stylists, barbers, technicians). */
class StaffController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/staff/Index', [
            'staff' => StaffMember::forTenant($tenant)->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(StaffRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $staff = StaffMember::create([...$request->validated(), 'tenant_id' => $tenant->id]);

        $this->activityLog->log($tenant, $request->user()->id, 'staff.created', 'StaffMember', $staff->id, ['name' => $staff->name]);

        return back()->with('success', "{$staff->name} added.");
    }

    public function update(StaffRequest $request, string $tenantSlug, int $staff): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $member = StaffMember::forTenant($tenant)->findOrFail($staff);

        $member->update($request->validated());

        $this->activityLog->log($tenant, $request->user()->id, 'staff.updated', 'StaffMember', $member->id, ['name' => $member->name]);

        return back()->with('success', "{$member->name} updated.");
    }

    /** Remove, or just deactivate when they already appear on sales (their history must stay). */
    public function destroy(Request $request, string $tenantSlug, int $staff): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $member = StaffMember::forTenant($tenant)->findOrFail($staff);

        if (OrderItem::where('staff_member_id', $member->id)->exists()) {
            $member->update(['is_active' => false]);

            return back()->with('success', "{$member->name} has past sales, so they were deactivated instead of deleted.");
        }

        $member->delete();

        $this->activityLog->log($tenant, $request->user()->id, 'staff.deleted', 'StaffMember', $member->id, ['name' => $member->name]);

        return back()->with('success', "{$member->name} removed.");
    }
}
