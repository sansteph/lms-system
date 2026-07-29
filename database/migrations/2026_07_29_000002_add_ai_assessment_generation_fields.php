<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'start_time')) {
                $table->time('start_time')->nullable()->after('assessment_date');
            }

            if (!Schema::hasColumn('assessments', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }

            if (!Schema::hasColumn('assessments', 'ai_generated')) {
                $table->boolean('ai_generated')->default(false)->after('question_paper_type');
            }

            if (!Schema::hasColumn('assessments', 'ai_source_content_ids')) {
                $table->json('ai_source_content_ids')->nullable()->after('ai_generated');
            }

            if (!Schema::hasColumn('assessments', 'ai_generation_payload')) {
                $table->longText('ai_generation_payload')->nullable()->after('ai_source_content_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $columns = [];

            foreach (['start_time', 'end_time', 'ai_generated', 'ai_source_content_ids', 'ai_generation_payload'] as $column) {
                if (Schema::hasColumn('assessments', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
