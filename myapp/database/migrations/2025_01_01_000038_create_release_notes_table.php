<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('version', 50);
            $table->text('summary')->nullable();
            $table->json('items');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_notes');
    }
};
