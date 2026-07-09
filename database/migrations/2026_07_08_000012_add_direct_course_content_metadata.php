<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            if (!Schema::hasColumn('contents', 'description')) {
                $table->text('description')->nullable()->after('content_title');
            }

            if (!Schema::hasColumn('contents', 'original_file_name')) {
                $table->string('original_file_name')->nullable()->after('student_preview_pdf_path');
            }

            if (!Schema::hasColumn('contents', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('original_file_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            foreach (['uploaded_by', 'original_file_name', 'description'] as $column) {
                if (Schema::hasColumn('contents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
