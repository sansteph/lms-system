<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('assessment_id');
            $table->string('question_type')->default('MCQ')->after('topic');

            $table->text('short_answer')->nullable()->after('correct_answer');
            $table->text('long_answer')->nullable()->after('short_answer');

            $table->text('explanation')->nullable()->after('long_answer');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->dropColumn([
                'topic',
                'question_type',
                'short_answer',
                'long_answer',
                'explanation',
            ]);
        });
    }
};
