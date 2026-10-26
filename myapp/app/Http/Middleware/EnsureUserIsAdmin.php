<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect('/admin/login');
        }

        // Deactivating an admin must also end a session that is already open.
        if (! Auth::guard('admin')->user()->is_active) {
            Auth::guard('admin')->logout();

            return redirect('/admin/login');
        }

        return $next($request);
    }
}
