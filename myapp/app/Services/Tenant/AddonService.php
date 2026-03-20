<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Addon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AddonService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Addon::forTenant($tenant);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $addonId): Addon
    {
        return Addon::forTenant($tenant)->findOrFail($addonId);
    }

    public function create(Tenant $tenant, array $data): Addon
    {
        return Addon::create([
            ...$data,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function update(Addon $addon, array $data): Addon
    {
        $addon->update($data);
        return $addon->fresh();
    }

    public function delete(Addon $addon): void
    {
        $addon->delete();
    }
}
