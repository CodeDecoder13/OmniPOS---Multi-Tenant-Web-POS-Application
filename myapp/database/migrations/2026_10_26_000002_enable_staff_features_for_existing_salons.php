<?php

use App\Enums\BusinessType;
use App\Models\Tenant\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Salons and barbershops that signed up before staff tracking existed get it switched on for
 * their branches. Only switches that were never set are touched, so it is safe to run twice
 * and never overrides an owner's choice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches') || ! Schema::hasColumn('branches', 'settings')) {
            return;
        }

        foreach ([BusinessType::BeautySalon, BusinessType::Barbershop] as $type) {
            $tenantIds = DB::table('tenants')->where('business_type', $type->value)->pluck('id');

            Branch::whereIn('tenant_id', $tenantIds)->get()->each(function (Branch $branch) use ($type) {
                $branch->update(['settings' => array_merge($type->branchSettings(), $branch->settings ?? [])]);
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: switching a feature back off is the owner's choice.
    }
};
