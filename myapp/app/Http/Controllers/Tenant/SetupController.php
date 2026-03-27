<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchRequest;
use App\Services\Tenant\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($tenant->branches()->count() > 0) {
            return redirect()->route('tenant.dashboard', ['tenant' => $tenant->slug]);
        }

        return Inertia::render('tenant/Setup', [
            'userEmail' => $request->user()->email,
            'tenantName' => $tenant->name,
        ]);
    }

    public function store(BranchRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->branchService->create($tenant, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tenant.dashboard', ['tenant' => $tenant->slug])
            ->with('success', 'Branch created successfully. Welcome to OmniPOS!');
    }
}
