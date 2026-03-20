<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('variation_group_name');
            $table->string('option_name');
            $table->decimal('price_modifier', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_variations');
    }
};
