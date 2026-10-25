<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * Nobody can give out more power than they hold. The business owner is exempt.
 *
 * Without this, anyone allowed to edit roles or user roles could create or assign a role with
 * every permission and promote themselves or a coworker.
 */
final class PermissionCeiling
{
    private static function isOwner(Request $request, Tenant $tenant): bool
    {
        return (int) $request->user()->id === (int) $tenant->owner_id;
    }

    /** @param  list<int|string>  $permissionIds */
    public static function assertPermissionIdsWithin(Request $request, Tenant $tenant, array $permissionIds): void
    {
        if (self::isOwner($request, $tenant)) {
            return;
        }

        $mine = $request->attributes->get('current_permissions', []);
        $requested = Permission::whereIn('id', $permissionIds)->pluck('slug')->all();

        abort_if(array_diff($requested, $mine) !== [], 403, 'You cannot grant permissions you do not have yourself.');
    }

    public static function assertRoleWithin(Request $request, Tenant $tenant, Role $role): void
    {
        if (self::isOwner($request, $tenant)) {
            return;
        }

        $mine = $request->attributes->get('current_permissions', []);

        // Any system role that carries every permission (Owner) is out of reach for a non-owner.
        abort_if($role->is_system && $role->slug === 'owner', 403, 'Only the organization owner can assign the Owner role.');
        abort_if(array_diff($role->permissions->pluck('slug')->all(), $mine) !== [], 403, 'You cannot assign a role with permissions you do not have yourself.');
    }

    /** A non-owner may not change the permissions of the role they themselves hold. */
    public static function assertNotOwnRole(Request $request, Tenant $tenant, Role $role): void
    {
        if (self::isOwner($request, $tenant)) {
            return;
        }

        $myRoleId = $request->attributes->get('current_role')?->id;

        abort_if($myRoleId !== null && (int) $myRoleId === (int) $role->id, 403, 'You cannot change the permissions of your own role.');
    }
}
