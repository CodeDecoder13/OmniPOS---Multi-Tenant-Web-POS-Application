<?php

namespace App\Services\Tenant;

use App\Enums\TableStatus;
use App\Models\Tenant;
use App\Models\Tenant\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TableService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Table::forTenant($tenant)
            ->with('branch:id,name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function getAvailableForBranch(Tenant $tenant, ?int $branchId): Collection
    {
        return Table::forTenant($tenant)
            ->where('is_active', true)
            ->where('status', TableStatus::Available)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'status']);
    }

    public function findForTenant(Tenant $tenant, int $tableId): Table
    {
        return Table::forTenant($tenant)
            ->with('branch:id,name')
            ->findOrFail($tableId);
    }

    public function create(Tenant $tenant, array $data): Table
    {
        return Table::create([
            ...$data,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function update(Table $table, array $data): Table
    {
        $table->update($data);
        return $table->fresh();
    }

    public function delete(Table $table): void
    {
        if ($table->orders()->where('status', 'completed')->exists() === false) {
            // Allow deletion — or check for active orders
        }

        if ($table->status === TableStatus::Occupied) {
            throw new \RuntimeException('Cannot delete an occupied table.');
        }

        $table->delete();
    }

    public function markOccupied(Table $table): void
    {
        $table->update(['status' => TableStatus::Occupied]);
    }

    public function markAvailable(Table $table): void
    {
        $table->update(['status' => TableStatus::Available]);
    }
}
