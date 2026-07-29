<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_quizzes', 'grade_level')) {
                $table->string('grade_level')->nullable()->after('audience');
            }
        });

        Schema::table('ai_quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_quiz_attempts', 'grade_level')) {
                $table->string('grade_level')->nullable()->after('attempt_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('ai_quiz_attempts', 'grade_level')) {
                $table->dropColumn('grade_level');
            }
        });

        Schema::table('ai_quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('ai_quizzes', 'grade_level')) {
                $table->dropColumn('grade_level');
            }
        });
    }
};
