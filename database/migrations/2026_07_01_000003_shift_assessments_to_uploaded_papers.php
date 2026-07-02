<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('question_paper_status')->default('Pending Approval')->after('file_path');
            $table->unsignedBigInteger('question_paper_reviewed_by')->nullable()->after('question_paper_status');
            $table->timestamp('question_paper_reviewed_at')->nullable()->after('question_paper_reviewed_by');
            $table->text('question_paper_feedback')->nullable()->after('question_paper_reviewed_at');
            $table->string('question_paper_preview_path')->nullable()->after('question_paper_feedback');
        });

        DB::table('assessments')
            ->select('file_path')
            ->orderBy('id')
            ->chunk(100, function ($assessments) {
                foreach ($assessments as $assessment) {
                    $this->movePathToPublic($assessment->file_path);
                }
            });

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->longText('answer_text')->nullable()->after('percentage');
            $table->string('answer_file_path')->nullable()->after('answer_text');
            $table->text('feedback')->nullable()->after('answer_file_path');
            $table->boolean('passed')->nullable()->after('feedback');
            $table->unsignedBigInteger('evaluated_by')->nullable()->after('passed');
            $table->timestamp('evaluated_at')->nullable()->after('evaluated_by');
        });

        DB::table('assessments')
            ->whereNotNull('file_path')
            ->update([
                'question_paper_status' => 'Pending Approval',
            ]);

        DB::table('assessments')
            ->select('file_path')
            ->orderBy('id')
            ->chunk(100, function ($assessments) {
                foreach ($assessments as $assessment) {
                    $this->movePathToPrivate($assessment->file_path);
                }
            });
    }

    public function down(): void
    {
        DB::table('assessments')
            ->select('question_paper_preview_path')
            ->orderBy('id')
            ->chunk(100, function ($assessments) {
                foreach ($assessments as $assessment) {
                    if (
                        $assessment->question_paper_preview_path &&
                        Storage::disk('local')->exists($assessment->question_paper_preview_path)
                    ) {
                        Storage::disk('local')->delete($assessment->question_paper_preview_path);
                    }
                }
            });

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->dropColumn([
                'answer_text',
                'answer_file_path',
                'feedback',
                'passed',
                'evaluated_by',
                'evaluated_at',
            ]);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'question_paper_status',
                'question_paper_reviewed_by',
                'question_paper_reviewed_at',
                'question_paper_feedback',
                'question_paper_preview_path',
            ]);
        });
    }

    private function movePathToPrivate($path): void
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return;
        }

        if (!Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
        }

        Storage::disk('public')->delete($path);
    }

    private function movePathToPublic($path): void
    {
        if (!$path || !Storage::disk('local')->exists($path)) {
            return;
        }

        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, Storage::disk('local')->get($path));
        }

        Storage::disk('local')->delete($path);
    }
};
