<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamp('visited_at')->useCurrent();

            $table->index(['url', 'visited_at']);
            $table->index('visited_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
