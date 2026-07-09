<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_plan_weeks') && !Schema::hasColumn('teaching_plan_weeks', 'release_reason')) {
            Schema::table('teaching_plan_weeks', function (Blueprint $table) {
                $table->string('release_reason')->nullable()->after('completed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_plan_weeks') && Schema::hasColumn('teaching_plan_weeks', 'release_reason')) {
            Schema::table('teaching_plan_weeks', function (Blueprint $table) {
                $table->dropColumn('release_reason');
            });
        }
    }
};
