<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'promotion_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('promotion_id')->nullable()->after('table_id');
                $table->decimal('promotion_discount', 10, 2)->default(0)->after('promotion_id');
                $table->foreign('promotion_id')->references('id')->on('promotions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['promotion_id', 'promotion_discount']);
        });
    }
};
