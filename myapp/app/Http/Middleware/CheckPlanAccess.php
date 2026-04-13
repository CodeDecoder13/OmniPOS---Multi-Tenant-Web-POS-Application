<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanAccess
{
    public function handle(Request $request, Closure $next, string ...$plans): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        if (! $tenant) {
            abort(403, 'Tenant not found.');
        }

        $currentPlan = $tenant->subscription?->plan?->slug;

        if (! $currentPlan || ! in_array($currentPlan, $plans)) {
            abort(403, 'Your current plan does not include access to this feature.');
        }

        return $next($request);
    }
}
