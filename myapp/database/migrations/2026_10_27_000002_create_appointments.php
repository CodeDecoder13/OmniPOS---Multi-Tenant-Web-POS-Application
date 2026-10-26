<?php

use App\Enums\BusinessType;
use App\Models\Tenant\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Appointments: a booked time with one staff member for one or more services.
 * Times are stored in UTC like everything else; the screens convert to the shop's local time.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone')->nullable();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                // booked, arrived, in_service, completed, cancelled, no_show
                $table->string('status')->default('booked');
                $table->text('notes')->nullable();
                // Filled in when the appointment is checked out at the POS.
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'starts_at']);
                $table->index(['staff_member_id', 'starts_at']);
            });
        }

        if (! Schema::hasTable('appointment_items')) {
            Schema::create('appointment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                // Snapshots, so a later price or name change does not rewrite an old booking.
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->unsignedSmallInteger('duration_minutes')->default(0);
            });
        }

        $this->addPermissions();
        $this->enableForExistingSalons();
    }

    private function addPermissions(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'View Appointments', 'slug' => 'appointments.view', 'group' => 'appointments'],
            ['name' => 'Manage Appointments', 'slug' => 'appointments.manage', 'group' => 'appointments'],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(['slug' => $p['slug']], [...$p, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissionIds = DB::table('permissions')->whereIn('slug', array_column($permissions, 'slug'))->pluck('id');

        foreach (DB::table('roles')->where('is_system', true)->where('slug', 'admin')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    /** Salons and barbershops that already exist get the switch on, unless the owner set it themselves. */
    private function enableForExistingSalons(): void
    {
        foreach ([BusinessType::BeautySalon, BusinessType::Barbershop] as $type) {
            $tenantIds = DB::table('tenants')->where('business_type', $type->value)->pluck('id');

            Branch::whereIn('tenant_id', $tenantIds)->get()->each(function (Branch $branch) use ($type) {
                $branch->update(['settings' => array_merge($type->branchSettings(), $branch->settings ?? [])]);
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: rolling back must not delete bookings.
    }
};
