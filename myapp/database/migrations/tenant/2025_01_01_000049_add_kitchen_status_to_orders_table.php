<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kitchen_status')->nullable()->after('status');
            $table->timestamp('kitchen_sent_at')->nullable()->after('kitchen_status');
            $table->timestamp('kitchen_completed_at')->nullable()->after('kitchen_sent_at');
            $table->text('kitchen_notes')->nullable()->after('kitchen_completed_at');

            $table->index(['branch_id', 'kitchen_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'kitchen_status']);
            $table->dropColumn(['kitchen_status', 'kitchen_sent_at', 'kitchen_completed_at', 'kitchen_notes']);
        });
    }
};
