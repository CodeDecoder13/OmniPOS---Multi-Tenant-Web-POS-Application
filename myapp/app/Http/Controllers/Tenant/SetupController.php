<?php

namespace App\Http\Controllers\Tenant;

use App\Support\PinGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchRequest;
use App\Models\TenantUser;
use App\Services\Tenant\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function __construct(
        private readonly BranchService $branchService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response|RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ($tenant->branches()->count() === 0) {
            return Inertia::render('tenant/Setup', [
                'step' => 1,
                'userEmail' => $request->user()->email,
                'tenantName' => $tenant->name,
            ]);
        }

        // Branch exists — check if user has PIN
        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($tenantUser && ! $tenantUser->pos_pin) {
            return Inertia::render('tenant/Setup', [
                'step' => 2,
                'userEmail' => $request->user()->email,
                'tenantName' => $tenant->name,
            ]);
        }

        return redirect()->route('tenant.dashboard', ['tenant' => $tenant->slug]);
    }

    public function store(BranchRequest $request, string $tenantSlug): RedirectResponse
    {
        // Setup only creates the FIRST branch. After that, branches go through the plan-limited Branches page.
        abort_if($request->attributes->get('current_tenant')->branches()->exists(), 403, 'Setup is already complete.');

        $tenant = $request->attributes->get('current_tenant');

        $branch = $this->branchService->create($tenant, $request->validated(), $request->user()->id);

        // Assign the owner to the newly created branch
        TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $request->user()->id)
            ->update(['branch_id' => $branch->id]);

        // Redirect back to setup so step 2 (PIN) shows
        return redirect()->route('tenant.setup', ['tenant' => $tenant->slug]);
    }

    public function storePin(Request $request, string $tenantSlug): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'digits_between:4,6', 'confirmed'],
        ]);

        $tenant = $request->attributes->get('current_tenant');
        $pin = $request->input('pin');

        PinGuard::assertNotLocked($request, $tenant);

        // Must be unique within the business (counts against the guess budget)
        if (PinGuard::isTakenByOther($request, $tenant, (int) $request->user()->id, $pin)) {
            return back()->withErrors(['pin' => 'Please choose a different PIN.']);
        }

        TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $request->user()->id)
            ->update(['pos_pin' => Hash::make($pin)]);

        return redirect()
            ->route('tenant.dashboard', ['tenant' => $tenant->slug])
            ->with('showWelcome', true)
            ->with('success', 'POS PIN set successfully. Welcome to OmniPOS!');
    }
}
