<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('challenge_token_hash', 64)->unique();
            $table->string('account_type');
            $table->unsignedBigInteger('account_id');
            $table->string('channel', 30);
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('resend_window_started_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            // The application always supplies the expiry; nullable avoids MySQL strict-mode defaults.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['account_type', 'account_id']);
            $table->index(['expires_at', 'locked_until']);
        });

        Schema::create('mfa_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('account_type')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('event', 80);
            $table->string('channel', 30)->nullable();
            $table->boolean('successful')->default(false);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_type', 'account_id', 'created_at']);
            $table->index(['event', 'successful']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_audit_logs');
        Schema::dropIfExists('mfa_challenges');
    }
};
