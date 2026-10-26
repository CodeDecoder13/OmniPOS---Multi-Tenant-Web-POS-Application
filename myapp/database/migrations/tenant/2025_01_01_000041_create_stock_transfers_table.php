<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_transfers')) {
            return; // already created (this folder was not migrated before)
        }

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('transfer_number')->unique();
            $table->foreignId('source_branch_id')->constrained('branches');
            $table->foreignId('destination_branch_id')->constrained('branches');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        // Intentionally empty. These tables existed in production before this migration was ever
        // recorded, so rolling back must never drop them. Drop them by hand if you really mean to.
    }
};
