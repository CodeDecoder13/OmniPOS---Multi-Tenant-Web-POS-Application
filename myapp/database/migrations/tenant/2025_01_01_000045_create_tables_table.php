<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tables')) {
            return; // already created (this folder was not migrated before)
        }

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(4);
            $table->string('status')->default('available');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        // Intentionally empty. These tables existed in production before this migration was ever
        // recorded, so rolling back must never drop them. Drop them by hand if you really mean to.
    }
};
