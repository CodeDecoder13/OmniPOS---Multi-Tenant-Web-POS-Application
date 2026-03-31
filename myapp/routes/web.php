<?php

use App\Models\Plan;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(\App\Http\Middleware\TrackPageVisit::class)->group(function () {
    Route::get('/', function () {
        return inertia('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'plans' => Plan::active()->get(['name', 'slug', 'price', 'features', 'max_branches', 'max_users', 'max_products']),
        ]);
    })->name('home');

    Route::get('/about', function () {
        return inertia('About', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    })->name('about');
});

Route::middleware(['auth', 'throttle:6,1'])->group(function () {
    Route::post('/email/verify-otp', [\App\Http\Controllers\Auth\VerifyEmailOtpController::class, 'verify'])
        ->name('verification.verify-otp');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();
        $tenant = $user->tenants()->first();

        if ($tenant) {
            return redirect("/{$tenant->slug}/dashboard")->with('showWelcome', true);
        }

        return inertia('Dashboard');
    })->name('dashboard');
});

Route::post('/promo-codes/validate', function (Request $request) {
    $request->validate([
        'code' => ['required', 'string'],
        'plan' => ['required', 'string'],
    ]);

    $promo = PromoCode::where('code', strtoupper($request->code))->first();

    if (! $promo || ! $promo->isValidForPlan($request->plan)) {
        return response()->json([
            'valid' => false,
            'message' => 'Invalid or expired promo code.',
        ], 422);
    }

    $message = $promo->discount_type->value === 'percentage'
        ? "{$promo->discount_value}% discount applied!"
        : '₱' . number_format($promo->discount_value, 2) . ' discount applied!';

    return response()->json([
        'valid' => true,
        'message' => $message,
        'discount_type' => $promo->discount_type->value,
        'discount_value' => $promo->discount_value,
    ]);
})->middleware('throttle:10,1');

// Mail preview routes (local only)
if (app()->isLocal()) {
    Route::get('/mail-preview/{type}', function (string $type) {
        $notification = match ($type) {
            'welcome' => new \App\Notifications\WelcomeNotification('My Coffee Shop'),
            'login' => new \App\Notifications\LoginAlertNotification('127.0.0.1', 'Mozilla/5.0 Chrome/120.0'),
            'verify' => new \App\Notifications\VerifyEmailOtpNotification('482916'),
            'reset' => new \Illuminate\Auth\Notifications\ResetPassword('fake-token-preview'),
            default => abort(404),
        };

        $user = \App\Models\User::first() ?? new \App\Models\User(['name' => 'John Doe', 'email' => 'john@example.com']);

        if ($type === 'verify') {
            return $notification->toMail($user)->render();
        }

        return $notification->toMail($user)->render();
    })->name('mail.preview');
}

require __DIR__.'/settings.php';
