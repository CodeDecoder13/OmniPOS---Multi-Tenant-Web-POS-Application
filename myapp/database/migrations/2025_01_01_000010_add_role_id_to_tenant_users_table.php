<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add nullable role_id first
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('tenant_id')->constrained()->cascadeOnDelete();
        });

        // Seed permissions if not yet seeded
        $this->seedPermissions();

        // Migrate data: create default roles for each tenant and assign role_id
        $this->migrateExistingData();

        // Make role_id NOT NULL and drop the old role column
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('tenant_id');
        });

        // Reverse mapping
        $tenantUsers = TenantUser::with('role')->get();
        foreach ($tenantUsers as $tu) {
            $tu->role = $tu->role?->slug ?? 'staff';
            $tu->saveQuietly();
        }

        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }

    private function seedPermissions(): void
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
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }

    private function migrateExistingData(): void
    {
        $allPermissions = Permission::pluck('id');
        $posPerms = Permission::whereIn('slug', ['pos.access', 'orders.view', 'products.view', 'inventory.view'])->pluck('id');
        $staffPerms = Permission::whereIn('slug', ['branches.view', 'users.view', 'products.view', 'inventory.view'])->pluck('id');
        $adminPerms = Permission::whereNotIn('slug', ['settings.manage'])->pluck('id');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $ownerRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all features',
                'is_system' => true,
            ]);
            $ownerRole->permissions()->sync($allPermissions);

            $adminRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access',
                'is_system' => true,
            ]);
            $adminRole->permissions()->sync($adminPerms);

            $cashierRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'POS and order access',
                'is_system' => true,
            ]);
            $cashierRole->permissions()->sync($posPerms);

            $staffRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Basic view access',
                'is_system' => true,
            ]);
            $staffRole->permissions()->sync($staffPerms);

            // Map old role strings to new role_id
            $roleMap = [
                'owner' => $ownerRole->id,
                'admin' => $adminRole->id,
                'staff' => $staffRole->id,
            ];

            $tenantUsers = TenantUser::where('tenant_id', $tenant->id)->get();
            foreach ($tenantUsers as $tu) {
                $tu->role_id = $roleMap[$tu->role] ?? $staffRole->id;
                $tu->saveQuietly();
            }
        }
    }
};
