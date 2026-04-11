<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Resolve default tenant for authenticated users on non-tenant routes
        if (! $request->attributes->get('current_tenant') && $request->user()) {
            $tenantUser = TenantUser::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->orderByDesc('last_login_at')
                ->with('role.permissions')
                ->first();

            if ($tenantUser) {
                $tenant = Tenant::where('id', $tenantUser->tenant_id)
                    ->where('is_active', true)
                    ->with('subscription.plan')
                    ->first();

                if ($tenant) {
                    $role = $tenantUser->role;

                    if ($role && $role->is_system && $role->slug === 'owner') {
                        $permissions = Permission::pluck('slug')->toArray();
                    } else {
                        $permissions = $role ? $role->permissions->pluck('slug')->toArray() : [];
                    }

                    $request->attributes->set('current_tenant', $tenant);
                    $request->attributes->set('current_tenant_user', $tenantUser);
                    $request->attributes->set('current_role', $role);
                    $request->attributes->set('current_permissions', $permissions);
                }
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'showWelcome' => fn () => $request->session()->get('showWelcome', false),
            ],
            'tenant' => function () use ($request) {
                $tenant = $request->attributes->get('current_tenant');

                if (! $tenant) {
                    return null;
                }

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'is_active' => $tenant->is_active,
                    'settings' => (function () use ($tenant) {
                        $settings = $tenant->data ?? [];
                        if (! empty($settings['receipt_logo'])) {
                            $settings['receipt_logo_url'] = \Illuminate\Support\Facades\Storage::disk('public')->url($settings['receipt_logo']);
                        }
                        return $settings;
                    })(),
                    'subscription' => $tenant->subscription ? [
                        'status' => $tenant->subscription->status,
                        'plan' => $tenant->subscription->plan ? [
                            'name' => $tenant->subscription->plan->name,
                            'slug' => $tenant->subscription->plan->slug,
                            'max_branches' => $tenant->subscription->plan->max_branches,
                            'max_users' => $tenant->subscription->plan->max_users,
                            'max_products' => $tenant->subscription->plan->max_products,
                        ] : null,
                    ] : null,
                ];
            },
            'tenantRole' => function () use ($request) {
                $role = $request->attributes->get('current_role');

                return $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_system' => $role->is_system,
                ] : null;
            },
            'tenantPermissions' => fn () => $request->attributes->get('current_permissions', []),
            'plans' => fn () => $request->attributes->get('current_tenant')
                ? Plan::active()->orderBy('price')->get(['name', 'slug', 'price', 'features', 'max_branches', 'max_users', 'max_products'])
                : [],
            'branchSettings' => function () use ($request) {
                $tenantUser = $request->attributes->get('current_tenant_user');

                if (! $tenantUser || ! $tenantUser->branch_id) {
                    return null;
                }

                $branch = \App\Models\Tenant\Branch::find($tenantUser->branch_id);

                return $branch?->getSettings();
            },
        ];
    }
}
