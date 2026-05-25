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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('user_type'); // Teacher / Student
            $table->string('user_id');

            $table->dateTime('login_time');
            $table->dateTime('logout_time')->nullable();

            $table->integer('total_duration_seconds')->default(0);

            $table->string('ip_address')->nullable();
            $table->string('browser')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
