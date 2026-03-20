<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Branches', 'slug' => 'branches.view', 'group' => 'branches'],
            ['name' => 'Create Branches', 'slug' => 'branches.create', 'group' => 'branches'],
            ['name' => 'Edit Branches', 'slug' => 'branches.edit', 'group' => 'branches'],
            ['name' => 'Delete Branches', 'slug' => 'branches.delete', 'group' => 'branches'],
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Invite Users', 'slug' => 'users.invite', 'group' => 'users'],
            ['name' => 'Edit User Role', 'slug' => 'users.edit-role', 'group' => 'users'],
            ['name' => 'Remove Users', 'slug' => 'users.remove', 'group' => 'users'],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'roles'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'group' => 'roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'roles'],
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'products'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'group' => 'products'],
            ['name' => 'Edit Products', 'slug' => 'products.edit', 'group' => 'products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'group' => 'products'],
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'group' => 'inventory'],
            ['name' => 'Access POS', 'slug' => 'pos.access', 'group' => 'pos'],
            ['name' => 'Apply Discounts', 'slug' => 'pos.discount', 'group' => 'pos'],
            ['name' => 'Void Transactions', 'slug' => 'pos.void', 'group' => 'pos'],
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders'],
            ['name' => 'Manage Orders', 'slug' => 'orders.manage', 'group' => 'orders'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'View Shifts', 'slug' => 'shifts.view', 'group' => 'shifts'],
            ['name' => 'Manage Shifts', 'slug' => 'shifts.manage', 'group' => 'shifts'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'View Suppliers', 'slug' => 'suppliers.view', 'group' => 'suppliers'],
            ['name' => 'Create Suppliers', 'slug' => 'suppliers.create', 'group' => 'suppliers'],
            ['name' => 'Edit Suppliers', 'slug' => 'suppliers.edit', 'group' => 'suppliers'],
            ['name' => 'Delete Suppliers', 'slug' => 'suppliers.delete', 'group' => 'suppliers'],
            ['name' => 'View Tables', 'slug' => 'tables.view', 'group' => 'tables'],
            ['name' => 'Create Tables', 'slug' => 'tables.create', 'group' => 'tables'],
            ['name' => 'Edit Tables', 'slug' => 'tables.edit', 'group' => 'tables'],
            ['name' => 'Delete Tables', 'slug' => 'tables.delete', 'group' => 'tables'],
            ['name' => 'View Promotions', 'slug' => 'promotions.view', 'group' => 'promotions'],
            ['name' => 'Create Promotions', 'slug' => 'promotions.create', 'group' => 'promotions'],
            ['name' => 'Edit Promotions', 'slug' => 'promotions.edit', 'group' => 'promotions'],
            ['name' => 'Delete Promotions', 'slug' => 'promotions.delete', 'group' => 'promotions'],
            ['name' => 'Access Kitchen Display', 'slug' => 'kitchen.access', 'group' => 'kitchen'],
            ['name' => 'Manage Kitchen Orders', 'slug' => 'kitchen.manage', 'group' => 'kitchen'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            );
        }
    }
}
