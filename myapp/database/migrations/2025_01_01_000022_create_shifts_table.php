<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->decimal('starting_cash', 12, 2);
            $table->decimal('ending_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('cash_difference', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['tenant_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
