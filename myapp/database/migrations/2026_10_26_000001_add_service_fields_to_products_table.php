<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A product is now one of three kinds:
 *   goods         physical stock that is counted        (is_food = false, is_service = false)
 *   prepared      made to order, no stock               (is_food = true)
 *   service       something you do, no stock, has time  (is_service = true)
 *
 * "Food" used to be the only way to say "no stock", which is wrong for a haircut.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'is_service')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_service')->default(false)->after('is_food');
            });
        }

        if (! Schema::hasColumn('products', 'duration_minutes')) {
            Schema::table('products', function (Blueprint $table) {
                // How long the service takes. Used by appointments later; optional.
                $table->unsignedSmallInteger('duration_minutes')->nullable()->after('is_service');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
            if (Schema::hasColumn('products', 'is_service')) {
                $table->dropColumn('is_service');
            }
        });
    }
};
