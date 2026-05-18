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
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_session_id');

            $table->string('user_type'); // Teacher / Student
            $table->string('user_id');

            $table->string('section_name')->nullable();
            $table->string('route_name')->nullable();
            $table->string('page_url')->nullable();

            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();

            $table->integer('duration_seconds')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
