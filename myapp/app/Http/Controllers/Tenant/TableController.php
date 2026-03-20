<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\TableRequest;
use App\Models\Tenant\Branch;
use App\Services\Tenant\TableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    public function __construct(
        private readonly TableService $tableService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/tables/Index', [
            'tables' => $this->tableService->list($tenant, $request),
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/tables/Create', [
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(TableRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->tableService->create($tenant, $request->validated());

        return redirect()
            ->route('tenant.tables.index', ['tenant' => $tenant->slug])
            ->with('success', 'Table created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $table): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $table = $this->tableService->findForTenant($tenant, $table);

        return Inertia::render('tenant/tables/Edit', [
            'table' => $table,
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(TableRequest $request, string $tenantSlug, int $table): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $table = $this->tableService->findForTenant($tenant, $table);

        $this->tableService->update($table, $request->validated());

        return redirect()
            ->route('tenant.tables.index', ['tenant' => $tenant->slug])
            ->with('success', 'Table updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $table): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $table = $this->tableService->findForTenant($tenant, $table);

        try {
            $this->tableService->delete($table);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.tables.index', ['tenant' => $tenant->slug])
            ->with('success', 'Table deleted successfully.');
    }
}
