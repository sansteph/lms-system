<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_content_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->unique()->constrained('contents')->cascadeOnDelete();
            $table->string('provider')->default('gemini');
            $table->string('model')->nullable();
            $table->string('source_file_path')->nullable();
            $table->string('source_hash', 128)->nullable();
            $table->longText('extracted_text')->nullable();
            $table->longText('summary')->nullable();
            $table->json('key_points')->nullable();
            $table->json('quiz_seed')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('audience')->default('student');
            $table->string('provider')->default('gemini');
            $table->string('model')->nullable();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('passing_marks')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['content_id', 'audience', 'status']);
        });

        Schema::create('ai_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_quiz_id')->constrained('ai_quizzes')->cascadeOnDelete();
            $table->unsignedInteger('question_order')->default(1);
            $table->string('question_type')->default('short_answer');
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->text('expected_answer')->nullable();
            $table->json('rubric')->nullable();
            $table->unsignedInteger('marks')->default(1);
            $table->timestamps();
        });

        Schema::create('ai_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_quiz_id')->constrained('ai_quizzes')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('attempt_type')->default('student');
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('percentage', 8, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('feedback')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'attempt_type', 'status']);
        });

        Schema::create('ai_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_quiz_attempt_id')->constrained('ai_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('ai_quiz_question_id')->constrained('ai_quiz_questions')->cascadeOnDelete();
            $table->longText('answer_text')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_quiz_answers');
        Schema::dropIfExists('ai_quiz_attempts');
        Schema::dropIfExists('ai_quiz_questions');
        Schema::dropIfExists('ai_quizzes');
        Schema::dropIfExists('ai_content_summaries');
    }
};
