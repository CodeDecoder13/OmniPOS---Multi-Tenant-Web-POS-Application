<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postgres does not index foreign keys automatically. These columns are filtered or joined
 * on every report, shift summary and order list, so they get indexes. Each one is skipped
 * if it (or the table/column) is already there.
 */
return new class extends Migration
{
    /** @var array<string, list<list<string>>> */
    private array $indexes = [
        'order_items' => [['order_id']],
        'payments' => [['order_id']],
        'orders' => [['shift_id'], ['tenant_id', 'branch_id', 'status', 'created_at']],
        'inventory_adjustments' => [['inventory_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $sets) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($sets as $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $t) use ($columns) {
                    $t->index($columns);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $sets) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($sets as $columns) {
                if (Schema::hasIndex($table, $columns)) {
                    Schema::table($table, function (Blueprint $t) use ($columns) {
                        $t->dropIndex($columns);
                    });
                }
            }
        }
    }
};
