<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'refunded_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('refunded_amount', 12, 2)->default(0)->after('total');
            });
        }

        if (!Schema::hasColumn('payments', 'refund_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('refund_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty. These tables existed in production before this migration was ever
        // recorded, so rolling back must never drop them. Drop them by hand if you really mean to.
    }
};
