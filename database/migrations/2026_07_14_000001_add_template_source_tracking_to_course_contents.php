<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('course_contents', 'source_template_course_content_id')) {
                $table->unsignedBigInteger('source_template_course_content_id')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('course_contents', 'source_template_content_id')) {
                $table->unsignedBigInteger('source_template_content_id')->nullable()->after('source_template_course_content_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_contents', function (Blueprint $table) {
            if (Schema::hasColumn('course_contents', 'source_template_content_id')) {
                $table->dropColumn('source_template_content_id');
            }

            if (Schema::hasColumn('course_contents', 'source_template_course_content_id')) {
                $table->dropColumn('source_template_course_content_id');
            }
        });
    }
};
