<?php

use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\PosPinController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\ShiftController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('{tenant}')
    ->middleware(['web', 'auth', 'verified', 'tenant'])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

        // Branches
        Route::middleware('can-do:branches.view')->group(function () {
            Route::get('branches', [BranchController::class, 'index'])->name('tenant.branches.index');
        });
        Route::middleware('can-do:branches.create')->group(function () {
            Route::get('branches/create', [BranchController::class, 'create'])->name('tenant.branches.create');
            Route::post('branches', [BranchController::class, 'store'])->name('tenant.branches.store');
        });
        Route::middleware('can-do:branches.edit')->group(function () {
            Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('tenant.branches.edit');
            Route::put('branches/{branch}', [BranchController::class, 'update'])->name('tenant.branches.update');
        });
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
            ->middleware('can-do:branches.delete')
            ->name('tenant.branches.destroy');

        // Users
        Route::middleware('can-do:users.view')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('tenant.users.index');
        });
        Route::post('users', [UserController::class, 'store'])
            ->name('tenant.users.store');
        Route::put('users/{user}', [UserController::class, 'update'])
            ->middleware('can-do:users.edit-role')
            ->name('tenant.users.update');
        Route::delete('users/{user}', [UserController::class, 'remove'])
            ->middleware('can-do:users.remove')
            ->name('tenant.users.remove');

        // Roles
        Route::middleware('can-do:roles.view')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('tenant.roles.index');
        });
        Route::middleware('can-do:roles.create')->group(function () {
            Route::get('roles/create', [RoleController::class, 'create'])->name('tenant.roles.create');
            Route::post('roles', [RoleController::class, 'store'])->name('tenant.roles.store');
        });
        Route::middleware('can-do:roles.edit')->group(function () {
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('tenant.roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('tenant.roles.update');
        });
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('can-do:roles.delete')
            ->name('tenant.roles.destroy');

        // Categories
        Route::middleware('can-do:products.view')->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->name('tenant.categories.index');
        });
        Route::middleware('can-do:products.create')->group(function () {
            Route::get('categories/create', [CategoryController::class, 'create'])->name('tenant.categories.create');
            Route::post('categories', [CategoryController::class, 'store'])->name('tenant.categories.store');
            Route::post('categories/inline', [CategoryController::class, 'storeInline'])->name('tenant.categories.store-inline');
        });
        Route::middleware('can-do:products.edit')->group(function () {
            Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('tenant.categories.edit');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('tenant.categories.update');
        });
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
            ->middleware('can-do:products.delete')
            ->name('tenant.categories.destroy');

        // Products
        Route::middleware('can-do:products.view')->group(function () {
            Route::get('products', [ProductController::class, 'index'])->name('tenant.products.index');
        });
        Route::middleware('can-do:products.create')->group(function () {
            Route::get('products/create', [ProductController::class, 'create'])->name('tenant.products.create');
            Route::post('products', [ProductController::class, 'store'])->name('tenant.products.store');
        });
        Route::middleware('can-do:products.edit')->group(function () {
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('tenant.products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('tenant.products.update');
        });
        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->middleware('can-do:products.delete')
            ->name('tenant.products.destroy');

        // Inventory
        Route::middleware('can-do:inventory.view')->group(function () {
            Route::get('inventory', [InventoryController::class, 'index'])->name('tenant.inventory.index');
            Route::get('inventory/{inventory}/history', [InventoryController::class, 'history'])->name('tenant.inventory.history');
        });
        Route::post('inventory/{inventory}/adjust', [InventoryController::class, 'adjust'])
            ->middleware('can-do:inventory.manage')
            ->name('tenant.inventory.adjust');

        // POS PIN (no permission guard — any authenticated tenant user)
        Route::post('pos/pin/verify', [PosPinController::class, 'verify'])
            ->middleware('throttle:5,1')
            ->name('tenant.pos.pin.verify');
        Route::post('pos/pin/set', [PosPinController::class, 'set'])
            ->name('tenant.pos.pin.set');
        Route::put('users/{user}/pin', [PosPinController::class, 'setForUser'])
            ->name('tenant.users.pin.set');

        // POS
        Route::middleware('can-do:pos.access')->group(function () {
            Route::get('pos', [PosController::class, 'index'])->name('tenant.pos.index');
            Route::get('pos/products', [PosController::class, 'products'])->name('tenant.pos.products');
            Route::get('pos/customers/search', [PosController::class, 'searchCustomers'])->name('tenant.pos.customers.search');
            Route::post('pos/checkout', [PosController::class, 'checkout'])->name('tenant.pos.checkout');
            Route::post('pos/shifts/open', [ShiftController::class, 'open'])->name('tenant.pos.shifts.open');
            Route::post('pos/shifts/close', [ShiftController::class, 'close'])->name('tenant.pos.shifts.close');
            Route::get('pos/shifts/status', [ShiftController::class, 'status'])->name('tenant.pos.shifts.status');
        });

        // Shifts
        Route::middleware('can-do:shifts.view')->group(function () {
            Route::get('shifts', [ShiftController::class, 'index'])->name('tenant.shifts.index');
            Route::get('shifts/{shift}', [ShiftController::class, 'show'])->name('tenant.shifts.show');
        });

        // Orders
        Route::middleware('can-do:orders.view')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('tenant.orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('tenant.orders.show');
            Route::get('orders/{order}/receipt/pdf', [OrderController::class, 'receiptPdf'])->name('tenant.orders.receipt.pdf');
        });
        Route::post('orders/{order}/void', [OrderController::class, 'void'])
            ->middleware('can-do:pos.void')
            ->name('tenant.orders.void');

        // Reports
        Route::middleware('can-do:reports.view')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('tenant.reports.index');
        });

        // Customers
        Route::middleware('can-do:orders.view')->group(function () {
            Route::get('customers', [CustomerController::class, 'index'])->name('tenant.customers.index');
        });
        Route::middleware('can-do:orders.manage')->group(function () {
            Route::get('customers/create', [CustomerController::class, 'create'])->name('tenant.customers.create');
            Route::post('customers', [CustomerController::class, 'store'])->name('tenant.customers.store');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('tenant.customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('tenant.customers.update');
            Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('tenant.customers.destroy');
        });

        // Settings
        Route::middleware('can-do:settings.manage')->group(function () {
            Route::get('settings', [SettingsController::class, 'edit'])->name('tenant.settings.edit');
            Route::put('settings', [SettingsController::class, 'update'])->name('tenant.settings.update');
        });
    });
