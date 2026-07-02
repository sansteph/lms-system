<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'assessment_category')) {
                $table->string('assessment_category')->default('Monthly')->after('assigned_class');
            }

            if (!Schema::hasColumn('assessments', 'assessment_date')) {
                $table->date('assessment_date')->nullable()->after('assessment_category');
            }
        });

        DB::table('assessments')
            ->whereNull('assessment_date')
            ->update(['assessment_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'assessment_date')) {
                $table->dropColumn('assessment_date');
            }

            if (Schema::hasColumn('assessments', 'assessment_category')) {
                $table->dropColumn('assessment_category');
            }
        });
    }
};
