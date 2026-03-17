<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        $tenant = Tenant::where('slug', $slug)
            ->with('subscription.plan')
            ->first();

        if (! $tenant) {
            abort(404);
        }

        if (! $tenant->is_active) {
            abort(403, 'This organization has been deactivated.');
        }

        $user = $request->user();

        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with('role.permissions')
            ->first();

        if (! $tenantUser) {
            abort(403, 'You do not have access to this organization.');
        }

        $role = $tenantUser->role;

        // Owner role always gets all permissions regardless of pivot table state
        if ($role && $role->is_system && $role->slug === 'owner') {
            $permissions = Permission::pluck('slug')->toArray();
        } else {
            $permissions = $role ? $role->permissions->pluck('slug')->toArray() : [];
        }

        $request->attributes->set('current_tenant', $tenant);
        $request->attributes->set('current_tenant_user', $tenantUser);
        $request->attributes->set('current_role', $role);
        $request->attributes->set('current_permissions', $permissions);

        return $next($request);
    }
}
