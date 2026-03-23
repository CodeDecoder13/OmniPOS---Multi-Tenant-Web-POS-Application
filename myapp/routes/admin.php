<?php

use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\PromoCodeController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Guest admin routes
    Route::middleware('web')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('admin.login');
        Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');
        Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');
    });

    // Protected admin routes
    Route::middleware(['web', 'admin'])->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

        // Tenants
        Route::get('tenants', [TenantController::class, 'index'])->name('admin.tenants.index');
        Route::get('tenants/create', [TenantController::class, 'create'])->name('admin.tenants.create');
        Route::post('tenants', [TenantController::class, 'store'])->name('admin.tenants.store');
        Route::get('tenants/{id}', [TenantController::class, 'show'])->name('admin.tenants.show');
        Route::get('tenants/{id}/edit', [TenantController::class, 'edit'])->name('admin.tenants.edit');
        Route::put('tenants/{id}', [TenantController::class, 'update'])->name('admin.tenants.update');
        Route::delete('tenants/{id}', [TenantController::class, 'destroy'])->name('admin.tenants.destroy');
        Route::patch('tenants/{id}/toggle', [TenantController::class, 'toggle'])->name('admin.tenants.toggle');

        // Users
        Route::get('users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('users/{id}', [UserController::class, 'show'])->name('admin.users.show');
        Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
        Route::patch('users/{id}/toggle-verified', [UserController::class, 'toggleVerified'])->name('admin.users.toggle-verified');

        // Plans
        Route::get('plans', [PlanController::class, 'index'])->name('admin.plans.index');
        Route::get('plans/create', [PlanController::class, 'create'])->name('admin.plans.create');
        Route::post('plans', [PlanController::class, 'store'])->name('admin.plans.store');
        Route::get('plans/{id}/edit', [PlanController::class, 'edit'])->name('admin.plans.edit');
        Route::put('plans/{id}', [PlanController::class, 'update'])->name('admin.plans.update');
        Route::delete('plans/{id}', [PlanController::class, 'destroy'])->name('admin.plans.destroy');
        Route::patch('plans/{id}/toggle', [PlanController::class, 'toggle'])->name('admin.plans.toggle');

        // Promo Codes
        Route::get('promo-codes', [PromoCodeController::class, 'index'])->name('admin.promo-codes.index');
        Route::get('promo-codes/create', [PromoCodeController::class, 'create'])->name('admin.promo-codes.create');
        Route::post('promo-codes', [PromoCodeController::class, 'store'])->name('admin.promo-codes.store');
        Route::get('promo-codes/{id}/edit', [PromoCodeController::class, 'edit'])->name('admin.promo-codes.edit');
        Route::put('promo-codes/{id}', [PromoCodeController::class, 'update'])->name('admin.promo-codes.update');
        Route::delete('promo-codes/{id}', [PromoCodeController::class, 'destroy'])->name('admin.promo-codes.destroy');

        // Admin Users
        Route::get('admins', [AdminController::class, 'index'])->name('admin.admins.index');
        Route::get('admins/create', [AdminController::class, 'create'])->name('admin.admins.create');
        Route::post('admins', [AdminController::class, 'store'])->name('admin.admins.store');
        Route::get('admins/{id}/edit', [AdminController::class, 'edit'])->name('admin.admins.edit');
        Route::put('admins/{id}', [AdminController::class, 'update'])->name('admin.admins.update');
        Route::delete('admins/{id}', [AdminController::class, 'destroy'])->name('admin.admins.destroy');

        // Subscriptions
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('admin.subscriptions.index');
        Route::get('subscriptions/{id}', [SubscriptionController::class, 'show'])->name('admin.subscriptions.show');
        Route::put('subscriptions/{id}', [SubscriptionController::class, 'update'])->name('admin.subscriptions.update');
        Route::patch('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('admin.subscriptions.cancel');
        Route::patch('subscriptions/{id}/reactivate', [SubscriptionController::class, 'reactivate'])->name('admin.subscriptions.reactivate');
        Route::patch('subscriptions/{id}/extend-trial', [SubscriptionController::class, 'extendTrial'])->name('admin.subscriptions.extend-trial');

        // Reports
        Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('admin.reports.revenue');
        Route::get('reports/tenants', [ReportController::class, 'tenants'])->name('admin.reports.tenants');
        Route::get('reports/users', [ReportController::class, 'users'])->name('admin.reports.users');
        Route::get('reports/subscriptions', [ReportController::class, 'subscriptions'])->name('admin.reports.subscriptions');

        // Activity Log
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('admin.activity-log.index');

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    });
});
