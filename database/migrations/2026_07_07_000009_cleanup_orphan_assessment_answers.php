<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assessment_answers')) {
            return;
        }

        if (Schema::hasTable('assessment_results')) {
            DB::table('assessment_answers')
                ->whereNotNull('assessment_result_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('assessment_results')
                        ->whereColumn('assessment_results.id', 'assessment_answers.assessment_result_id');
                })
                ->delete();
        }

        if (Schema::hasTable('assessments')) {
            DB::table('assessment_answers')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('assessments')
                        ->whereColumn('assessments.id', 'assessment_answers.assessment_id');
                })
                ->delete();
        }

        if (Schema::hasTable('students')) {
            DB::table('assessment_answers')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('students')
                        ->whereColumn('students.id', 'assessment_answers.student_id');
                })
                ->delete();
        }

        if (Schema::hasTable('assessment_questions')) {
            DB::table('assessment_answers')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('assessment_questions')
                        ->whereColumn('assessment_questions.id', 'assessment_answers.question_id');
                })
                ->delete();
        }
    }

    public function down(): void
    {
        //
    }
};
