<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magic_login_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 128);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->boolean('remember')->default(false);
            $table->string('redirect_to', 2048)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->ipAddress('used_ip')->nullable();
            $table->string('used_ua')->nullable();
            $table->timestamps();

            $table->index('expires_at');
            $table->index('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_login_tokens');
    }
};
