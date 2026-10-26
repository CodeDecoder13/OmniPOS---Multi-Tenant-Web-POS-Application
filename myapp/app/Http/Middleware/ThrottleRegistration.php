<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sign-up seeds roles, categories and promotions for a whole new business, so it is rate limited per IP.
 * Fortify only defines limiters for login and two-factor, so this guards POST /register.
 */
class ThrottleRegistration
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->is('register')) {
            return $next($request);
        }

        $key = 'register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Too many sign-up attempts. Please try again in a few minutes.');
        }

        RateLimiter::hit($key, 600);

        return $next($request);
    }
}
