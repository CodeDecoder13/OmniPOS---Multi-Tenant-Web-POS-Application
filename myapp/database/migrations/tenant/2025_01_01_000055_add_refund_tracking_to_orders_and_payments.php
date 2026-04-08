<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('total');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('refund_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refund_id');
        });
    }
};
