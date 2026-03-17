<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('low_stock_threshold')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
            $table->index(['tenant_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
