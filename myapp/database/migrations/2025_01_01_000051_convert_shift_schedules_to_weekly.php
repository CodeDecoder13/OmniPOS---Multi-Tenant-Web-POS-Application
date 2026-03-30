<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing scheduled_date values to days_of_week
        if (Schema::hasColumn('shift_schedules', 'scheduled_date')) {
            // Add new column first
            Schema::table('shift_schedules', function (Blueprint $table) {
                $table->json('days_of_week')->nullable()->after('branch_id');
            });

            // Migrate existing data: convert date to day abbreviation
            DB::statement("
                UPDATE shift_schedules
                SET days_of_week = jsonb_build_array(
                    CASE EXTRACT(DOW FROM scheduled_date)
                        WHEN 0 THEN 'sun'
                        WHEN 1 THEN 'mon'
                        WHEN 2 THEN 'tue'
                        WHEN 3 THEN 'wed'
                        WHEN 4 THEN 'thu'
                        WHEN 5 THEN 'fri'
                        WHEN 6 THEN 'sat'
                    END
                )
                WHERE scheduled_date IS NOT NULL
            ");

            // Drop old column and indexes
            Schema::table('shift_schedules', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'scheduled_date']);
                $table->dropIndex(['tenant_id', 'user_id', 'scheduled_date']);
                $table->dropColumn('scheduled_date');
            });

            // Make days_of_week required and add new index
            Schema::table('shift_schedules', function (Blueprint $table) {
                $table->json('days_of_week')->nullable(false)->change();
                $table->index(['tenant_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('shift_schedules', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('branch_id');
        });

        Schema::table('shift_schedules', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'user_id']);
            $table->dropColumn('days_of_week');
            $table->index(['tenant_id', 'scheduled_date']);
            $table->index(['tenant_id', 'user_id', 'scheduled_date']);
        });
    }
};
