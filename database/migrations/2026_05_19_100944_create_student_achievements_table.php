<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id');

            $table->string('achievement_type');

            $table->string('title');

            $table->string('organizer')->nullable();

            $table->text('description')->nullable();

            $table->date('achievement_date')->nullable();

            $table->string('position')->nullable();

            $table->string('certificate_file');

            $table->enum('verification_status', [
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};  