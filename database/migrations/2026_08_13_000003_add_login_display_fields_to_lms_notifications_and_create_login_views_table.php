<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('lms_notifications', 'notification_type')) {
                $table->string('notification_type')->nullable()->after('target');
            }

            if (!Schema::hasColumn('lms_notifications', 'login_display_limit')) {
                $table->unsignedTinyInteger('login_display_limit')->default(0)->after('status');
            }
        });

        if (!Schema::hasTable('lms_notification_login_views')) {
            Schema::create('lms_notification_login_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notification_id')->constrained('lms_notifications')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id');
                $table->string('viewer_role', 30)->default('Teacher');
                $table->string('institute')->nullable();
                $table->unsignedTinyInteger('display_count')->default(0);
                $table->timestamp('last_displayed_at')->nullable();
                $table->timestamps();

                $table->unique(['notification_id', 'user_id']);
                $table->index(['user_id', 'viewer_role']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_notification_login_views')) {
            Schema::dropIfExists('lms_notification_login_views');
        }

        Schema::table('lms_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('lms_notifications', 'notification_type')) {
                $table->dropColumn('notification_type');
            }

            if (Schema::hasColumn('lms_notifications', 'login_display_limit')) {
                $table->dropColumn('login_display_limit');
            }
        });
    }
};
