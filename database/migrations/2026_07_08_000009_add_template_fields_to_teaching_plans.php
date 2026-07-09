<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_plans', 'is_template')) {
                $table->boolean('is_template')->default(false)->after('id');
            }

            if (!Schema::hasColumn('teaching_plans', 'parent_template_id')) {
                $table->unsignedBigInteger('parent_template_id')->nullable()->after('is_template');
            }

            if (!Schema::hasColumn('teaching_plans', 'institute_id')) {
                $table->unsignedBigInteger('institute_id')->nullable()->after('institute');
            }

            if (!Schema::hasColumn('teaching_plans', 'title')) {
                $table->string('title')->nullable()->after('parent_template_id');
            }

            if (!Schema::hasColumn('teaching_plans', 'current_batch')) {
                $table->unsignedInteger('current_batch')->nullable()->after('contents_per_week');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teaching_plans', function (Blueprint $table) {
            $drop = array_filter([
                Schema::hasColumn('teaching_plans', 'is_template') ? 'is_template' : null,
                Schema::hasColumn('teaching_plans', 'parent_template_id') ? 'parent_template_id' : null,
                Schema::hasColumn('teaching_plans', 'institute_id') ? 'institute_id' : null,
                Schema::hasColumn('teaching_plans', 'title') ? 'title' : null,
                Schema::hasColumn('teaching_plans', 'current_batch') ? 'current_batch' : null,
            ]);

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
