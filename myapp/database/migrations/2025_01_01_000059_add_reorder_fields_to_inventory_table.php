<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventory', 'reorder_point')) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->integer('reorder_point')->nullable()->after('low_stock_threshold');
                $table->integer('reorder_quantity')->nullable()->after('reorder_point');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn(['reorder_point', 'reorder_quantity']);
        });
    }
};
