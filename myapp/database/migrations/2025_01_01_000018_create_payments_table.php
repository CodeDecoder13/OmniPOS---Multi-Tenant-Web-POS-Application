<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('reference_number')->nullable();
            $table->string('status')->default('completed');
            $table->decimal('amount_tendered', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
