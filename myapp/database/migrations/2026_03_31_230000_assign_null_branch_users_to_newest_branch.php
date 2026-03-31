<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all tenant_users with null branch_id, grouped by tenant
        $nullBranchUsers = DB::table('tenant_users')
            ->whereNull('branch_id')
            ->get();

        $tenantIds = $nullBranchUsers->pluck('tenant_id')->unique();

        foreach ($tenantIds as $tenantId) {
            // Find the newest active branch for this tenant
            $newestBranch = DB::table('branches')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($newestBranch) {
                DB::table('tenant_users')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $newestBranch->id]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible — we don't know which users had null before
    }
};
