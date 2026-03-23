<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Order;
use App\Models\Tenant\Shift;
use App\Models\Tenant\StockTransfer;
use App\Models\Tenant\PurchaseOrder;
use App\Models\TenantUser;
use DomainException;
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
        if (Order::where('branch_id', $branch->id)->exists()) {
            throw new DomainException('Cannot delete a branch with existing orders.');
        }

        if (Shift::where('branch_id', $branch->id)->exists()) {
            throw new DomainException('Cannot delete a branch with existing shifts.');
        }

        if (StockTransfer::where('source_branch_id', $branch->id)->orWhere('destination_branch_id', $branch->id)->exists()) {
            throw new DomainException('Cannot delete a branch with existing stock transfers.');
        }

        if (PurchaseOrder::where('branch_id', $branch->id)->exists()) {
            throw new DomainException('Cannot delete a branch with existing purchase orders.');
        }

        if (TenantUser::where('branch_id', $branch->id)->exists()) {
            throw new DomainException('Cannot delete a branch with assigned users. Reassign users first.');
        }

        $branch->delete();
    }

    public function findForTenant(Tenant $tenant, int $branchId): Branch
    {
        return Branch::forTenant($tenant)->findOrFail($branchId);
    }

    public function updateSettings(Branch $branch, array $settings): Branch
    {
        $branch->update(['settings' => $settings]);

        return $branch->fresh();
    }
}
