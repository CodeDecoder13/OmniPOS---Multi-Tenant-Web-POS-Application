<?php

namespace App\Services\Tenant;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use DomainException;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function list(Tenant $tenant)
    {
        return Role::forTenant($tenant->id)
            ->withCount('tenantUsers')
            ->with('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }

    public function find(Tenant $tenant, int $roleId): Role
    {
        return Role::forTenant($tenant->id)
            ->with('permissions')
            ->findOrFail($roleId);
    }

    public function create(Tenant $tenant, array $data, array $permissionIds): Role
    {
        return DB::transaction(function () use ($tenant, $data, $permissionIds) {
            $role = Role::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'slug' => str($data['name'])->slug()->toString(),
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);

            $role->permissions()->sync($permissionIds);

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data, array $permissionIds): Role
    {
        return DB::transaction(function () use ($role, $data, $permissionIds) {
            $updateData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ];

            // Don't change slug on system roles
            if (! $role->is_system) {
                $updateData['slug'] = str($data['name'])->slug()->toString();
            }

            $role->update($updateData);

            // Protect owner role — always keep all permissions
            if ($role->is_system && $role->slug === 'owner') {
                $permissionIds = Permission::pluck('id')->toArray();
            }

            $role->permissions()->sync($permissionIds);

            return $role->load('permissions');
        });
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new DomainException('System roles cannot be deleted.');
        }

        if ($role->tenantUsers()->exists()) {
            throw new DomainException('Cannot delete a role that is assigned to users. Reassign users first.');
        }

        $role->delete();
    }

    public function createDefaultRoles(Tenant $tenant): Role
    {
        $allPermissions = Permission::pluck('id');
        $adminPerms = Permission::whereNotIn('slug', ['settings.manage'])->pluck('id');
        $cashierPerms = Permission::whereIn('slug', [
            'pos.access', 'pos.discount', 'orders.view', 'products.view', 'inventory.view',
        ])->pluck('id');
        $staffPerms = Permission::whereIn('slug', [
            'branches.view', 'users.view', 'products.view', 'inventory.view', 'orders.view',
        ])->pluck('id');

        $ownerRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'slug' => 'owner',
            'description' => 'Full access to all features',
            'is_system' => true,
        ]);
        $ownerRole->permissions()->sync($allPermissions);

        $adminRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrative access',
            'is_system' => true,
        ]);
        $adminRole->permissions()->sync($adminPerms);

        $cashierRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'POS and order access',
            'is_system' => true,
        ]);
        $cashierRole->permissions()->sync($cashierPerms);

        $staffRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff',
            'slug' => 'staff',
            'description' => 'Basic view access',
            'is_system' => true,
        ]);
        $staffRole->permissions()->sync($staffPerms);

        return $ownerRole;
    }

    public function syncSystemRolePermissions(Tenant $tenant): void
    {
        $allPermissions = Permission::pluck('id');
        $adminPerms = Permission::whereNotIn('slug', ['settings.manage'])->pluck('id');
        $cashierPerms = Permission::whereIn('slug', [
            'pos.access', 'pos.discount', 'orders.view', 'products.view', 'inventory.view',
        ])->pluck('id');
        $staffPerms = Permission::whereIn('slug', [
            'branches.view', 'users.view', 'products.view', 'inventory.view', 'orders.view',
        ])->pluck('id');

        $roles = Role::forTenant($tenant->id)->where('is_system', true)->get();

        foreach ($roles as $role) {
            $perms = match ($role->slug) {
                'owner' => $allPermissions,
                'admin' => $adminPerms,
                'cashier' => $cashierPerms,
                'staff' => $staffPerms,
                default => collect(),
            };
            $role->permissions()->sync($perms);
        }
    }

    public static function syncAllTenants(): void
    {
        $service = app(self::class);
        foreach (Tenant::all() as $tenant) {
            $service->syncSystemRolePermissions($tenant);
        }
    }

    public function getGroupedPermissions(): array
    {
        return Permission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group')
            ->toArray();
    }
}
