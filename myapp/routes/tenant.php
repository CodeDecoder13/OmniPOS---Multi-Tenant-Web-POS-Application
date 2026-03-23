<?php

use App\Http\Controllers\Tenant\AddonController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BranchMenuController;
use App\Http\Controllers\Tenant\BranchSettingsController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\KitchenDisplayController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\PosPinController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\ShiftController;
use App\Http\Controllers\Tenant\ShiftScheduleController;
use App\Http\Controllers\Tenant\StockTransferController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\TableController;
use App\Http\Controllers\Tenant\PromotionController;
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
            Route::get('branches/{branch}/menu', [BranchMenuController::class, 'index'])->name('tenant.branches.menu');
            Route::put('branches/{branch}/menu', [BranchMenuController::class, 'update'])->name('tenant.branches.menu.update');
            Route::get('branches/{branch}/settings', [BranchSettingsController::class, 'edit'])->name('tenant.branches.settings');
            Route::put('branches/{branch}/settings', [BranchSettingsController::class, 'update'])->name('tenant.branches.settings.update');
        });
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
            ->middleware('can-do:branches.delete')
            ->name('tenant.branches.destroy');

        // Users
        Route::middleware('can-do:users.view')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('tenant.users.index');
        });
        Route::middleware(['can-do:users.edit-role', 'throttle:30,1'])->group(function () {
            Route::post('users', [UserController::class, 'store'])->name('tenant.users.store');
            Route::post('users/import/validate', [UserController::class, 'validateImport'])->name('tenant.users.import.validate');
            Route::post('users/import', [UserController::class, 'import'])->name('tenant.users.import');
            Route::put('users/{user}', [UserController::class, 'update'])->name('tenant.users.update');
            Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('tenant.users.toggle-active');
        });
        Route::delete('users/{user}', [UserController::class, 'remove'])
            ->middleware('can-do:users.remove')
            ->name('tenant.users.remove');

        // Roles
        Route::middleware('can-do:roles.view')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('tenant.roles.index');
        });
        Route::middleware(['can-do:roles.create', 'throttle:30,1'])->group(function () {
            Route::get('roles/create', [RoleController::class, 'create'])->name('tenant.roles.create');
            Route::post('roles', [RoleController::class, 'store'])->name('tenant.roles.store');
        });
        Route::middleware(['can-do:roles.edit', 'throttle:30,1'])->group(function () {
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
        Route::middleware(['can-do:inventory.view', 'branch-feature:inventory_tracking'])->group(function () {
            Route::get('inventory', [InventoryController::class, 'index'])->name('tenant.inventory.index');
            Route::get('inventory/{inventory}/history', [InventoryController::class, 'history'])->name('tenant.inventory.history');
        });
        Route::post('inventory/{inventory}/adjust', [InventoryController::class, 'adjust'])
            ->middleware(['can-do:inventory.manage', 'branch-feature:inventory_tracking'])
            ->name('tenant.inventory.adjust');

        // POS PIN (no permission guard — any authenticated tenant user)
        Route::post('pos/pin/verify', [PosPinController::class, 'verify'])
            ->middleware('throttle:5,1')
            ->name('tenant.pos.pin.verify');
        Route::post('pos/pin/set', [PosPinController::class, 'set'])
            ->middleware('throttle:5,1')
            ->name('tenant.pos.pin.set');
        Route::put('users/{user}/pin', [PosPinController::class, 'setForUser'])
            ->middleware('throttle:5,1')
            ->name('tenant.users.pin.set');

        // POS
        Route::middleware(['can-do:pos.access', 'branch-feature:pos_enabled'])->group(function () {
            Route::get('pos', [PosController::class, 'index'])->name('tenant.pos.index');
            Route::get('pos/products', [PosController::class, 'products'])->name('tenant.pos.products');
            Route::get('pos/customers/search', [PosController::class, 'searchCustomers'])->name('tenant.pos.customers.search');
            Route::post('pos/checkout', [PosController::class, 'checkout'])
                ->middleware('throttle:60,1')
                ->name('tenant.pos.checkout');
            Route::post('pos/promotions/apply', [PromotionController::class, 'applyCode'])->name('tenant.pos.promotions.apply');
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
            Route::get('reports/export', [ReportController::class, 'export'])->name('tenant.reports.export');
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

        // Shift Schedules
        Route::middleware('can-do:users.edit-role')->group(function () {
            Route::get('shift-schedules', [ShiftScheduleController::class, 'index'])->name('tenant.shift-schedules.index');
            Route::post('shift-schedules', [ShiftScheduleController::class, 'store'])->name('tenant.shift-schedules.store');
            Route::put('shift-schedules/{schedule}', [ShiftScheduleController::class, 'update'])->name('tenant.shift-schedules.update');
            Route::delete('shift-schedules/{schedule}', [ShiftScheduleController::class, 'destroy'])->name('tenant.shift-schedules.destroy');
        });

        // Suppliers
        Route::middleware('can-do:suppliers.view')->group(function () {
            Route::get('suppliers', [SupplierController::class, 'index'])->name('tenant.suppliers.index');
        });
        Route::middleware('can-do:suppliers.create')->group(function () {
            Route::get('suppliers/create', [SupplierController::class, 'create'])->name('tenant.suppliers.create');
            Route::post('suppliers', [SupplierController::class, 'store'])->name('tenant.suppliers.store');
        });
        Route::middleware('can-do:suppliers.edit')->group(function () {
            Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('tenant.suppliers.edit');
            Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('tenant.suppliers.update');
        });
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
            ->middleware('can-do:suppliers.delete')
            ->name('tenant.suppliers.destroy');

        // Add-ons
        Route::middleware('can-do:products.view')->group(function () {
            Route::get('addons', [AddonController::class, 'index'])->name('tenant.addons.index');
        });
        Route::middleware('can-do:products.create')->group(function () {
            Route::get('addons/create', [AddonController::class, 'create'])->name('tenant.addons.create');
            Route::post('addons', [AddonController::class, 'store'])->name('tenant.addons.store');
        });
        Route::middleware('can-do:products.edit')->group(function () {
            Route::get('addons/{addon}/edit', [AddonController::class, 'edit'])->name('tenant.addons.edit');
            Route::put('addons/{addon}', [AddonController::class, 'update'])->name('tenant.addons.update');
        });
        Route::delete('addons/{addon}', [AddonController::class, 'destroy'])
            ->middleware('can-do:products.delete')
            ->name('tenant.addons.destroy');

        // Stock Transfers
        Route::middleware(['can-do:inventory.view', 'branch-feature:inventory_tracking'])->group(function () {
            Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('tenant.stock-transfers.index');
            Route::get('stock-transfers/{transfer}', [StockTransferController::class, 'show'])->name('tenant.stock-transfers.show');
        });
        Route::middleware(['can-do:inventory.manage', 'branch-feature:inventory_tracking'])->group(function () {
            Route::get('stock-transfers-create', [StockTransferController::class, 'create'])->name('tenant.stock-transfers.create');
            Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('tenant.stock-transfers.store');
            Route::post('stock-transfers/{transfer}/ship', [StockTransferController::class, 'ship'])->name('tenant.stock-transfers.ship');
            Route::post('stock-transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('tenant.stock-transfers.receive');
            Route::post('stock-transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('tenant.stock-transfers.cancel');
        });

        // Purchase Orders
        Route::middleware(['can-do:inventory.view', 'branch-feature:inventory_tracking'])->group(function () {
            Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('tenant.purchase-orders.index');
            Route::get('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('tenant.purchase-orders.show');
        });
        Route::middleware(['can-do:inventory.manage', 'branch-feature:inventory_tracking'])->group(function () {
            Route::get('purchase-orders-create', [PurchaseOrderController::class, 'create'])->name('tenant.purchase-orders.create');
            Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('tenant.purchase-orders.store');
            Route::get('purchase-orders/{purchase_order}/edit', [PurchaseOrderController::class, 'edit'])->name('tenant.purchase-orders.edit');
            Route::put('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'update'])->name('tenant.purchase-orders.update');
            Route::post('purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('tenant.purchase-orders.send');
            Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('tenant.purchase-orders.receive');
            Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('tenant.purchase-orders.cancel');
        });

        // Tables
        Route::middleware('can-do:tables.view')->group(function () {
            Route::get('tables', [TableController::class, 'index'])->name('tenant.tables.index');
        });
        Route::middleware('can-do:tables.create')->group(function () {
            Route::get('tables/create', [TableController::class, 'create'])->name('tenant.tables.create');
            Route::post('tables', [TableController::class, 'store'])->name('tenant.tables.store');
        });
        Route::middleware('can-do:tables.edit')->group(function () {
            Route::get('tables/{table}/edit', [TableController::class, 'edit'])->name('tenant.tables.edit');
            Route::put('tables/{table}', [TableController::class, 'update'])->name('tenant.tables.update');
        });
        Route::delete('tables/{table}', [TableController::class, 'destroy'])
            ->middleware('can-do:tables.delete')
            ->name('tenant.tables.destroy');

        // Promotions
        Route::middleware('can-do:promotions.view')->group(function () {
            Route::get('promotions', [PromotionController::class, 'index'])->name('tenant.promotions.index');
        });
        Route::middleware('can-do:promotions.create')->group(function () {
            Route::get('promotions/create', [PromotionController::class, 'create'])->name('tenant.promotions.create');
            Route::post('promotions', [PromotionController::class, 'store'])->name('tenant.promotions.store');
        });
        Route::middleware('can-do:promotions.edit')->group(function () {
            Route::get('promotions/{promotion}/edit', [PromotionController::class, 'edit'])->name('tenant.promotions.edit');
            Route::put('promotions/{promotion}', [PromotionController::class, 'update'])->name('tenant.promotions.update');
        });
        Route::delete('promotions/{promotion}', [PromotionController::class, 'destroy'])
            ->middleware('can-do:promotions.delete')
            ->name('tenant.promotions.destroy');

        // Kitchen Display
        Route::middleware(['can-do:kitchen.access', 'branch-feature:kitchen_display'])->group(function () {
            Route::get('kitchen', [KitchenDisplayController::class, 'index'])->name('tenant.kitchen.index');
            Route::get('kitchen/poll', [KitchenDisplayController::class, 'poll'])->name('tenant.kitchen.poll');
            Route::patch('kitchen/{order}/status', [KitchenDisplayController::class, 'updateStatus'])->name('tenant.kitchen.update-status');
            Route::get('kitchen/{order}/kot/pdf', [KitchenDisplayController::class, 'kotPdf'])->name('tenant.kitchen.kot.pdf');
        });

        // Settings
        Route::middleware('can-do:settings.manage')->group(function () {
            Route::get('settings', [SettingsController::class, 'edit'])->name('tenant.settings.edit');
            Route::put('settings', [SettingsController::class, 'update'])->name('tenant.settings.update');
        });
    });
