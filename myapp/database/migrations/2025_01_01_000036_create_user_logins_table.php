<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at')->useCurrent();

            $table->index(['user_id', 'logged_in_at']);
            $table->index(['tenant_id', 'logged_in_at']);
            $table->index('logged_in_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logins');
    }
};
