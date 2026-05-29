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
        Schema::create('certificate_verification_logs', function (Blueprint $table) {
            $table->id();

            $table->string('verifier_name');
            $table->string('verifier_email');
            $table->text('verification_reason')->nullable();

            $table->string('certificate_code');
            $table->string('verification_status')->default('failed');

            $table->unsignedBigInteger('certificate_id')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_verification_logs');
    }
};
