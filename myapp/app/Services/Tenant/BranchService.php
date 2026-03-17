<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchService
{
    public function list(Tenant $tenant, int $perPage = 10): LengthAwarePaginator
    {
        return Branch::forTenant($tenant)
            ->with('creator:id,name')
            ->latest()
            ->paginate($perPage);
    }

    public function create(Tenant $tenant, array $data, int $userId): Branch
    {
        return Branch::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'created_by' => $userId,
        ]);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->fresh();
    }

    public function delete(Branch $branch): void
    {
        $branch->delete();
    }

    public function findForTenant(Tenant $tenant, int $branchId): Branch
    {
        return Branch::forTenant($tenant)->findOrFail($branchId);
    }
}
