<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('content_library_items')) {
            return;
        }

        Schema::table('content_library_items', function (Blueprint $table) {
            if (!Schema::hasColumn('content_library_items', 'institute_id')) {
                $table->unsignedBigInteger('institute_id')->nullable()->after('institute');
            }
        });

        foreach ([
            'assigned_class',
            'section',
            'tags',
            'preview_pdf_path',
            'student_file_path',
            'student_preview_pdf_path',
            'student_original_file_name',
        ] as $column) {
            if (Schema::hasColumn('content_library_items', $column)) {
                Schema::table('content_library_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('content_library_items')) {
            return;
        }

        Schema::table('content_library_items', function (Blueprint $table) {
            if (!Schema::hasColumn('content_library_items', 'assigned_class')) {
                $table->string('assigned_class')->nullable();
            }

            if (!Schema::hasColumn('content_library_items', 'section')) {
                $table->string('section')->nullable();
            }

            if (!Schema::hasColumn('content_library_items', 'tags')) {
                $table->string('tags')->nullable();
            }

            if (!Schema::hasColumn('content_library_items', 'preview_pdf_path')) {
                $table->string('preview_pdf_path')->nullable();
            }

            if (!Schema::hasColumn('content_library_items', 'student_file_path')) {
                $table->string('student_file_path')->nullable();
            }

            if (!Schema::hasColumn('content_library_items', 'student_preview_pdf_path')) {
                $table->string('student_preview_pdf_path')->nullable();
            }

            if (!Schema::hasColumn('content_library_items', 'student_original_file_name')) {
                $table->string('student_original_file_name')->nullable();
            }
        });
    }
};
