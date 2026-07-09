<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_content_sessions', function (Blueprint $table) {
            $table->string('institute')->nullable()->after('id');
            $table->unsignedBigInteger('teaching_plan_id')->nullable()->after('course_content_id');
            $table->string('class')->nullable()->after('stem_engineer_id');
            $table->string('section')->nullable()->after('class');
            $table->string('session_day')->nullable()->after('section');
            $table->date('session_date')->nullable()->after('session_day');
            $table->time('start_time')->nullable()->after('session_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('planned_topic')->nullable()->after('end_time');
            $table->string('delivered_topic')->nullable()->after('planned_topic');
            $table->text('remarks')->nullable()->after('delivered_topic');
        });
    }

    public function down(): void
    {
        Schema::table('class_content_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'institute',
                'teaching_plan_id',
                'class',
                'section',
                'session_day',
                'session_date',
                'start_time',
                'end_time',
                'planned_topic',
                'delivered_topic',
                'remarks',
            ]);
        });
    }
};
