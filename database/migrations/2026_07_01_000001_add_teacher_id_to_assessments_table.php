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
            if (!Schema::hasColumn('assessments', 'content_id')) {
                $table->unsignedBigInteger('content_id')->nullable()->after('status');
            }

            $table->unsignedBigInteger('teacher_id')->nullable()->after('content_id');
        });

        $classes = DB::table('classes')->get();

        foreach ($classes as $class) {
            $assignedClass = trim($class->class_name . ' ' . $class->section);

            $teacher = DB::table('users')
                ->where('name', $class->class_teacher)
                ->where('institute', $class->institute)
                ->where('role', 'Teacher')
                ->first();

            if (!$teacher) {
                continue;
            }

            DB::table('assessments')
                ->where('assigned_class', $assignedClass)
                ->where('institute', $class->institute)
                ->whereNull('teacher_id')
                ->update(['teacher_id' => $teacher->id]);
        }
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('teacher_id');
        });
    }
};
