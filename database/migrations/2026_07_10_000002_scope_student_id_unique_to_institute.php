<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            try {
                $table->dropUnique('students_student_id_unique');
            } catch (\Throwable $e) {
                //
            }

            $table->unique(['student_id', 'institute'], 'students_student_id_institute_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            try {
                $table->dropUnique('students_student_id_institute_unique');
            } catch (\Throwable $e) {
                //
            }

            $table->unique('student_id', 'students_student_id_unique');
        });
    }
};
