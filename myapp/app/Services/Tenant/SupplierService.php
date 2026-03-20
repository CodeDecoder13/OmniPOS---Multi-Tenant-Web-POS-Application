<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class SupplierService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::forTenant($tenant)
            ->withCount('products');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $supplierId): Supplier
    {
        return Supplier::forTenant($tenant)
            ->withCount('products')
            ->findOrFail($supplierId);
    }

    public function create(Tenant $tenant, array $data, int $userId): Supplier
    {
        return Supplier::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'created_by' => $userId,
        ]);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): void
    {
        if ($supplier->purchaseOrders()->whereNotIn('status', ['cancelled', 'received'])->exists()) {
            throw new \RuntimeException('Cannot delete supplier with active purchase orders.');
        }

        $supplier->delete();
    }
}
