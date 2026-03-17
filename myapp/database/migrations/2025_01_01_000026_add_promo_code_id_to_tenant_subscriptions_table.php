<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('plan_id')->constrained('promo_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
        });
    }
};
