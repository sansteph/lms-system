<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_plans', 'ai_training_start_date')) {
                $table->date('ai_training_start_date')->nullable()->after('release_policy');
            }
        });

        if (Schema::hasColumn('teaching_plans', 'ai_training_start_date')) {
            $defaultStartDate = config('ai.content.auto_generation_start_date', '2026-07-31');

            DB::table('teaching_plans')
                ->where('is_template', false)
                ->whereNull('ai_training_start_date')
                ->whereIn('status', ['active', 'completed'])
                ->update(['ai_training_start_date' => $defaultStartDate]);
        }
    }

    public function down(): void
    {
        Schema::table('teaching_plans', function (Blueprint $table) {
            if (Schema::hasColumn('teaching_plans', 'ai_training_start_date')) {
                $table->dropColumn('ai_training_start_date');
            }
        });
    }
};
