<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantUser;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
            return Inertia::render('tenant/Deactivated', [
                'tenantName' => $tenant->name,
            ])->toResponse($request);
        }

        $user = $request->user();

        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with('role.permissions')
            ->first();

        if (! $tenantUser) {
            abort(403, 'You do not have access to this organization.');
        }

        if (! $tenantUser->is_active) {
            abort(403, 'Your account has been deactivated in this organization.');
        }

        // Throttled last_login_at update (every 15 minutes)
        if (! $tenantUser->last_login_at || $tenantUser->last_login_at->diffInMinutes(now()) >= 15) {
            $tenantUser->update(['last_login_at' => now()]);
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
