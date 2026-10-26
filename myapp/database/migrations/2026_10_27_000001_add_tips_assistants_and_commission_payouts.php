<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tips: collected on top of the sale total, never counted as revenue.
 * Assistant: a second person on a line who takes a percent share of that line's commission.
 * Payouts: a record that a staff member was paid for a date range (a snapshot of what was owed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'tip_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('tip_amount', 10, 2)->default(0)->after('tax_amount');
                // Who the tip is for. Blank = shared between the staff on the sale.
                $table->foreignId('tip_staff_member_id')->nullable()->after('tip_amount')
                    ->constrained('staff_members')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('order_items', 'assistant_staff_member_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('assistant_staff_member_id')->nullable()->after('staff_member_id')
                    ->constrained('staff_members')->nullOnDelete();
                // Percent of the line (0 to 100) that goes to the assistant; the main person keeps the rest.
                $table->decimal('assistant_share', 5, 2)->default(0)->after('assistant_staff_member_id');
            });
        }

        if (! Schema::hasTable('commission_payouts')) {
            Schema::create('commission_payouts', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
                $table->date('period_from');
                $table->date('period_to');
                $table->decimal('sales', 12, 2)->default(0);
                $table->decimal('commission', 12, 2)->default(0);
                $table->decimal('tips', 12, 2)->default(0);
                $table->decimal('amount', 12, 2);
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at');
                $table->timestamps();

                $table->index(['tenant_id', 'staff_member_id', 'period_from', 'period_to'], 'payouts_lookup');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: rolling back must not delete payout history.
    }
};
