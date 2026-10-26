<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/admin.php';
            require __DIR__.'/../routes/tenant.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \App\Http\Middleware\ThrottleRegistration::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'tenant' => IdentifyTenant::class,
            'can-do' => \App\Http\Middleware\CheckPermission::class,
            'branch-feature' => \App\Http\Middleware\CheckBranchFeature::class,
            'check-plan' => \App\Http\Middleware\CheckPlanAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business-rule failures thrown as abort(422, '...') (for example "Only a pending transfer can be shipped")
        // would otherwise show Inertia's generic error page. Send the person back with the message as a toast instead.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 422 && $e->getMessage() !== '' && $request->header('X-Inertia')) {
                return back()->with('error', $e->getMessage());
            }
        });
    })->create();
