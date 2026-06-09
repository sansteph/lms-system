<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('assessment_id');

            $table->unsignedBigInteger('user_id');

            $table->string('user_type'); // Student / Teacher

            $table->timestamp('started_at')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->string('status')->default('Started');
            // Started, Submitted, TimedOut, AutoSubmitted

            $table->integer('violation_count')->default(0);

            $table->timestamp('last_violation_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_sessions');
    }
};
