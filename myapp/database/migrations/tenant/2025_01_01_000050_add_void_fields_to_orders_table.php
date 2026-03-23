<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('voided_by')->nullable()->after('status');
            $table->text('void_reason')->nullable()->after('voided_by');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['voided_by', 'void_reason', 'voided_at']);
        });
    }
};
