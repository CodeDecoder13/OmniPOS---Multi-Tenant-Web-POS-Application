<?php

use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\PromoCodeController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Guest admin routes
    Route::middleware('web')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('admin.login');
        Route::post('login', [LoginController::class, 'login']);
        Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');
    });

    // Protected admin routes
    Route::middleware(['web', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('tenants', [TenantController::class, 'index'])->name('admin.tenants.index');
        Route::get('tenants/{id}', [TenantController::class, 'show'])->name('admin.tenants.show');
        Route::patch('tenants/{id}/toggle', [TenantController::class, 'toggle'])->name('admin.tenants.toggle');
        Route::get('users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('plans', [PlanController::class, 'index'])->name('admin.plans.index');
        Route::patch('plans/{id}', [PlanController::class, 'update'])->name('admin.plans.update');

        Route::get('promo-codes', [PromoCodeController::class, 'index'])->name('admin.promo-codes.index');
        Route::get('promo-codes/create', [PromoCodeController::class, 'create'])->name('admin.promo-codes.create');
        Route::post('promo-codes', [PromoCodeController::class, 'store'])->name('admin.promo-codes.store');
        Route::get('promo-codes/{id}/edit', [PromoCodeController::class, 'edit'])->name('admin.promo-codes.edit');
        Route::put('promo-codes/{id}', [PromoCodeController::class, 'update'])->name('admin.promo-codes.update');
        Route::delete('promo-codes/{id}', [PromoCodeController::class, 'destroy'])->name('admin.promo-codes.destroy');
    });
});
