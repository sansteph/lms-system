<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_plans') && Schema::hasColumn('teaching_plans', 'teacher_id')) {
            Schema::table('teaching_plans', function (Blueprint $table) {
                $table->dropColumn('teacher_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_plans') && !Schema::hasColumn('teaching_plans', 'teacher_id')) {
            Schema::table('teaching_plans', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('course_id');
            });
        }
    }
};
