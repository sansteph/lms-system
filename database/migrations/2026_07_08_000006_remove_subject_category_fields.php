<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_library_items') && Schema::hasColumn('content_library_items', 'subject')) {
            Schema::table('content_library_items', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }

        if (Schema::hasTable('contents') && Schema::hasColumn('contents', 'course_category')) {
            Schema::table('contents', function (Blueprint $table) {
                $table->dropColumn('course_category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('content_library_items') && !Schema::hasColumn('content_library_items', 'subject')) {
            Schema::table('content_library_items', function (Blueprint $table) {
                $table->string('subject')->nullable();
            });
        }

        if (Schema::hasTable('contents') && !Schema::hasColumn('contents', 'course_category')) {
            Schema::table('contents', function (Blueprint $table) {
                $table->string('course_category')->nullable()->after('content_title');
            });
        }
    }
};
