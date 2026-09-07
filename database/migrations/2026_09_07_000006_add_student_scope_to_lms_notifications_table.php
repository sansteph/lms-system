<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('lms_notifications', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('institute');
                $table->index(['student_id', 'notification_type']);
            }

            if (!Schema::hasColumn('lms_notifications', 'component_key')) {
                $table->string('component_key')->nullable()->after('student_id');
                $table->index(['student_id', 'component_key']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('lms_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('lms_notifications', 'component_key')) {
                $table->dropIndex(['student_id', 'component_key']);
                $table->dropColumn('component_key');
            }

            if (Schema::hasColumn('lms_notifications', 'student_id')) {
                $table->dropIndex(['student_id', 'notification_type']);
                $table->dropColumn('student_id');
            }
        });
    }
};
