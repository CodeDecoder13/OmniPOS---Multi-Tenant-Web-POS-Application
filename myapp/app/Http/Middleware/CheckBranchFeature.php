<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchFeature
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $tenantUser = $request->attributes->get('current_tenant_user');

        if (! $tenantUser || ! $tenantUser->branch_id) {
            return $next($request);
        }

        $branch = Branch::find($tenantUser->branch_id);

        if (! $branch) {
            return $next($request);
        }

        foreach ($features as $feature) {
            if (! $branch->getSetting($feature)) {
                abort(403, 'This feature is not enabled for your branch.');
            }
        }

        return $next($request);
    }
}
