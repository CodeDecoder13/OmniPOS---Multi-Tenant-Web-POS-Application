<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shifts', 'hours_rendered')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('hours_rendered', 8, 2)->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        // Intentionally empty. These tables existed in production before this migration was ever
        // recorded, so rolling back must never drop them. Drop them by hand if you really mean to.
    }
};
