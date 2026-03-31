<?php

namespace App\Http\Controllers\Tenant;

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
        $tenant = $request->attributes->get('current_tenant');

        $this->branchService->create($tenant, $request->validated(), $request->user()->id);

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

        // Check uniqueness within tenant
        $otherUsers = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', '!=', $request->user()->id)
            ->whereNotNull('pos_pin')
            ->get();

        foreach ($otherUsers as $other) {
            if (Hash::check($pin, $other->pos_pin)) {
                return back()->withErrors(['pin' => 'This PIN is already in use by another user.']);
            }
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
