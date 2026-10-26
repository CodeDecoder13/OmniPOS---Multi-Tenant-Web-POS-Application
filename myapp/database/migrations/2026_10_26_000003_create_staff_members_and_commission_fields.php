<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff who do the work (stylists, barbers, technicians) are their own list, not login users:
 * most of them never sign in, and they should not use up paid user seats.
 *
 *  staff_members              the people
 *  order_items.staff_member_id   who did this line of a sale
 *  products.commission_rate      optional per-service override of the person's default rate
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_members')) {
            Schema::create('staff_members', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->string('name');
                $table->string('phone')->nullable();
                // Default commission as a percent of what they sold (after discounts, before tax).
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
            });
        }

        if (! Schema::hasColumn('order_items', 'staff_member_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('staff_member_id')->nullable()->after('product_id')
                    ->constrained('staff_members')->nullOnDelete();
                $table->index('staff_member_id');
            });
        }

        if (! Schema::hasColumn('products', 'commission_rate')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->nullable()->after('cost_price');
            });
        }

        $this->addPermissions();
    }

    /** New permission rows, and the built-in Admin role of every business gets them (owners get all anyway). */
    private function addPermissions(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'Manage Staff', 'slug' => 'staff.manage', 'group' => 'staff'],
            ['name' => 'View Staff Commissions', 'slug' => 'staff.report', 'group' => 'staff'],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $p['slug']],
                [...$p, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('slug', array_column($permissions, 'slug'))->pluck('id');
        $adminRoleIds = DB::table('roles')->where('is_system', true)->where('slug', 'admin')->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: dropping staff data on a rollback would lose commission history.
    }
};
