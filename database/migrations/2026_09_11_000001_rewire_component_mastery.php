<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_mastery_course_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('fingerprint', 64);
            $table->json('components');
            $table->timestamps();
            $table->unique(['course_id', 'fingerprint']);
        });
        // Keep historical submissions; old papers must not be offered under the new rules.
        DB::table('assessments')->where('assessment_category', 'Component Mastery')
            ->orderBy('id')->each(function ($assessment) {
                DB::table('assessments')->where('id', $assessment->id)->update([
                    'status' => 0,
                    'assessment_title' => 'Basics in '.$assessment->component_label,
                ]);
                DB::table('certificates')->where('certificate_type', 'Component Mastery')
                    ->where('final_classification', 'Component Mastery - '.$assessment->component_label)
                    ->update(['final_classification' => 'Basics in '.$assessment->component_label]);
            });
        // Older approvals replaced the component name with a grade classification.
        DB::table('certificates')->where('certificate_type', 'Component Mastery')
            ->where('final_classification', 'not like', 'Basics in %')->orderBy('id')
            ->each(function ($certificate) {
                $labels = DB::table('assessment_results as results')
                    ->join('assessments', 'assessments.id', '=', 'results.assessment_id')
                    ->where('results.student_id', $certificate->student_id)
                    ->where('assessments.assessment_category', 'Component Mastery')
                    ->whereNotNull('assessments.component_label')
                    ->pluck('assessments.component_label')->unique();
                if ($labels->count() === 1) {
                    DB::table('certificates')->where('id', $certificate->id)
                        ->update(['final_classification' => 'Basics in '.$labels->first()]);
                }
            });
        DB::table('lms_notifications')->where('notification_type', 'component_mastery_eligible')->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        Schema::dropIfExists('component_mastery_course_scans');
    }
};
