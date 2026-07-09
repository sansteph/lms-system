<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_plans', 'start_date')) {
                $table->date('start_date')->nullable()->after('content_id');
            }

            if (!Schema::hasColumn('teaching_plans', 'release_day')) {
                $table->string('release_day')->default('Friday')->after('start_date');
            }

            if (!Schema::hasColumn('teaching_plans', 'contents_per_week')) {
                $table->unsignedInteger('contents_per_week')->default(2)->after('release_day');
            }

            if (!Schema::hasColumn('teaching_plans', 'release_policy')) {
                $table->string('release_policy')->default('release_next_only_if_previous_completed')->after('contents_per_week');
            }

            if (!Schema::hasColumn('teaching_plans', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('release_policy');
            }
        });

        Schema::create('teaching_plan_weeks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teaching_plan_id');
            $table->unsignedInteger('week_number');
            $table->date('week_start_date')->nullable();
            $table->date('week_end_date')->nullable();
            $table->date('release_date')->nullable();
            $table->string('status')->default('locked');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['teaching_plan_id', 'week_number']);
        });

        Schema::create('teaching_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teaching_plan_id');
            $table->unsignedBigInteger('teaching_plan_week_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('course_content_id')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('status')->default('locked');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('class_content_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('class_content_sessions', 'teaching_plan_week_id')) {
                $table->unsignedBigInteger('teaching_plan_week_id')->nullable()->after('teaching_plan_id');
            }

            if (!Schema::hasColumn('class_content_sessions', 'teaching_plan_item_id')) {
                $table->unsignedBigInteger('teaching_plan_item_id')->nullable()->after('teaching_plan_week_id');
            }

            if (!Schema::hasColumn('class_content_sessions', 'delivered_content_id')) {
                $table->unsignedBigInteger('delivered_content_id')->nullable()->after('content_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_content_sessions', function (Blueprint $table) {
            $drop = array_filter([
                Schema::hasColumn('class_content_sessions', 'teaching_plan_week_id') ? 'teaching_plan_week_id' : null,
                Schema::hasColumn('class_content_sessions', 'teaching_plan_item_id') ? 'teaching_plan_item_id' : null,
                Schema::hasColumn('class_content_sessions', 'delivered_content_id') ? 'delivered_content_id' : null,
            ]);

            if ($drop) {
                $table->dropColumn($drop);
            }
        });

        Schema::dropIfExists('teaching_plan_items');
        Schema::dropIfExists('teaching_plan_weeks');

        Schema::table('teaching_plans', function (Blueprint $table) {
            $drop = array_filter([
                Schema::hasColumn('teaching_plans', 'start_date') ? 'start_date' : null,
                Schema::hasColumn('teaching_plans', 'release_day') ? 'release_day' : null,
                Schema::hasColumn('teaching_plans', 'contents_per_week') ? 'contents_per_week' : null,
                Schema::hasColumn('teaching_plans', 'release_policy') ? 'release_policy' : null,
                Schema::hasColumn('teaching_plans', 'created_by') ? 'created_by' : null,
            ]);

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
