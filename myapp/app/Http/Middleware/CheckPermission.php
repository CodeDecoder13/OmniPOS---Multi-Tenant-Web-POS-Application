<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $userPermissions = $request->attributes->get('current_permissions', []);

        foreach ($permissions as $permission) {
            if (! in_array($permission, $userPermissions)) {
                abort(403, 'You do not have permission to perform this action.');
            }
        }

        return $next($request);
    }
}
