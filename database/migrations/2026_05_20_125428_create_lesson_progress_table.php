<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progresses', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('student_id');

            $table->unsignedBigInteger('content_id');

            $table->boolean('is_completed')->default(false);

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progresses');
    }
};