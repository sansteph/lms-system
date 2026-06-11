<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('assessment_result_id')->nullable();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('question_id');

            $table->string('question_type')->default('MCQ');
            $table->text('submitted_answer')->nullable();

            $table->boolean('is_correct')->nullable();
            $table->integer('marks_awarded')->default(0);
            $table->string('review_status')->default('Auto Graded');
            // Auto Graded, Pending Review, Reviewed

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
