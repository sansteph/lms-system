<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteWhereMissing('assessment_answers', 'assessment_id', 'assessments');
        $this->deleteWhereMissing('assessment_answers', 'student_id', 'students');
        $this->deleteWhereMissing('assessment_answers', 'assessment_result_id', 'assessment_results');

        $this->deleteWhereMissing('assessment_results', 'assessment_id', 'assessments');
        $this->deleteWhereMissing('assessment_results', 'student_id', 'students');

        $this->deleteWhereMissing('assessment_sessions', 'assessment_id', 'assessments');
        $this->deleteUserTypeOrphans('assessment_sessions');

        $this->deleteWhereMissing('certificates', 'student_id', 'students');
        $this->deleteWhereMissing('certificates', 'independent_learner_id', 'independent_learners');
        $this->deleteWhereMissing('certificates', 'course_id', 'courses');
        $this->deleteWhereMissing('certificate_verification_logs', 'certificate_id', 'certificates');
        $this->deleteCertificateCodeOrphans();

        $this->deleteWhereMissing('student_achievements', 'student_id', 'students');
        $this->deleteWhereMissing('teacher_achievements', 'user_id', 'users');
        $this->deleteWhereMissing('lesson_progress', 'student_id', 'students');
        $this->deleteWhereMissing('lesson_progress', 'independent_learner_id', 'independent_learners');
        $this->deleteWhereMissing('lesson_progress', 'content_id', 'contents');

        $this->deleteWhereMissing('course_enrollments', 'learner_id', 'independent_learners');
        $this->deleteWhereMissing('course_enrollments', 'course_id', 'courses');

        $this->deleteWhereMissing('course_contents', 'course_id', 'courses');
        $this->deleteWhereMissing('course_contents', 'content_id', 'contents');

        $this->deleteWhereMissing('teaching_plan_items', 'teaching_plan_id', 'teaching_plans');
        $this->deleteWhereMissing('teaching_plan_items', 'teaching_plan_week_id', 'teaching_plan_weeks');
        $this->deleteWhereMissing('teaching_plan_items', 'course_id', 'courses');
        $this->deleteWhereMissing('teaching_plan_items', 'course_content_id', 'course_contents');
        $this->deleteWhereMissing('teaching_plan_items', 'content_id', 'contents');
        $this->deleteWhereMissing('teaching_plan_weeks', 'teaching_plan_id', 'teaching_plans');
        $this->deleteWhereMissing('teaching_plans', 'course_id', 'courses');
        $this->deleteWhereMissing('teaching_plan_items', 'teaching_plan_id', 'teaching_plans');
        $this->deleteWhereMissing('teaching_plan_weeks', 'teaching_plan_id', 'teaching_plans');

        $this->deleteWhereMissing('class_content_sessions', 'course_id', 'courses');
        $this->deleteWhereMissing('class_content_sessions', 'course_content_id', 'course_contents');
        $this->deleteWhereMissing('class_content_sessions', 'teaching_plan_id', 'teaching_plans');
        $this->deleteWhereMissing('class_content_sessions', 'teaching_plan_week_id', 'teaching_plan_weeks');
        $this->deleteWhereMissing('class_content_sessions', 'teaching_plan_item_id', 'teaching_plan_items');
        $this->deleteWhereMissing('class_content_sessions', 'content_id', 'contents');
        $this->deleteWhereMissing('class_content_sessions', 'stem_engineer_id', 'users');

        $this->deleteWhereMissing('class_timetables', 'class_id', 'classes');
        $this->deleteWhereMissing('class_timetables', 'content_id', 'contents');

        $this->deleteUserTypeOrphans('user_sessions');
        $this->deleteUserTypeOrphans('user_activity_logs');
        $this->deleteWhereMissing('user_activity_logs', 'user_session_id', 'user_sessions');

        $this->deleteMySpaceOrphans();
    }

    public function down(): void
    {
        // Data cleanup cannot be safely reversed.
    }

    private function deleteWhereMissing(string $table, string $column, string $parentTable, string $parentColumn = 'id'): void
    {
        if (
            !Schema::hasTable($table) ||
            !Schema::hasTable($parentTable) ||
            !Schema::hasColumn($table, $column) ||
            !Schema::hasColumn($parentTable, $parentColumn)
        ) {
            return;
        }

        DB::table($table)
            ->whereNotNull($column)
            ->whereNotExists(function ($query) use ($table, $column, $parentTable, $parentColumn) {
                $query->selectRaw('1')
                    ->from($parentTable)
                    ->whereColumn("{$parentTable}.{$parentColumn}", "{$table}.{$column}");
            })
            ->delete();
    }

    private function deleteUserTypeOrphans(string $table): void
    {
        if (
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, 'user_type') ||
            !Schema::hasColumn($table, 'user_id')
        ) {
            return;
        }

        foreach (['Admin', 'Teacher', 'InstituteAdmin'] as $type) {
            DB::table($table)
                ->where('user_type', $type)
                ->whereNotExists(function ($query) use ($table) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', "{$table}.user_id");
                })
                ->delete();
        }

        DB::table($table)
            ->where('user_type', 'Student')
            ->whereNotExists(function ($query) use ($table) {
                $query->selectRaw('1')
                    ->from('students')
                    ->whereColumn('students.id', "{$table}.user_id");
            })
            ->delete();
    }

    private function deleteMySpaceOrphans(): void
    {
        if (!Schema::hasTable('my_spaces')) {
            return;
        }

        foreach (['Admin', 'Teacher', 'InstituteAdmin'] as $type) {
            DB::table('my_spaces')
                ->where('created_by_type', $type)
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'my_spaces.created_by_id');
                })
                ->delete();
        }

        DB::table('my_spaces')
            ->where('created_by_type', 'Student')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('students')
                    ->whereColumn('students.id', 'my_spaces.created_by_id');
            })
            ->delete();
    }

    private function deleteCertificateCodeOrphans(): void
    {
        if (
            !Schema::hasTable('certificate_verification_logs') ||
            !Schema::hasColumn('certificate_verification_logs', 'certificate_code') ||
            !Schema::hasTable('certificates') ||
            !Schema::hasColumn('certificates', 'certificate_code')
        ) {
            return;
        }

        DB::table('certificate_verification_logs')
            ->whereNotNull('certificate_code')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('certificates')
                    ->whereColumn('certificates.certificate_code', 'certificate_verification_logs.certificate_code');
            })
            ->delete();
    }
};
