<?php

namespace App\Http\Middleware;

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
                    'settings' => $tenant->data ?? [],
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
        ];
    }
}
