<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('variation_options')) {
            return; // already created (this folder was not migrated before)
        }

        Schema::create('variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price_modifier', 10, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally empty. These tables existed in production before this migration was ever
        // recorded, so rolling back must never drop them. Drop them by hand if you really mean to.
    }
};
