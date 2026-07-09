<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_content_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('class_id');
            $table->unsignedBigInteger('course_content_id')->nullable()->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_content_sessions', function (Blueprint $table) {
            $table->dropColumn(['course_id', 'course_content_id']);
        });

        //
    }
};
