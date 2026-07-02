<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->string('student_file_path')->nullable()->after('preview_pdf_path');
            $table->string('student_preview_pdf_path')->nullable()->after('student_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn([
                'student_file_path',
                'student_preview_pdf_path',
            ]);
        });
    }
};
