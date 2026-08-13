<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_plan_items', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_plan_items', 'completed_by')) {
                $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');
            }

            if (!Schema::hasColumn('teaching_plan_items', 'completed_by_role')) {
                $table->string('completed_by_role', 30)->nullable()->after('completed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teaching_plan_items', function (Blueprint $table) {
            if (Schema::hasColumn('teaching_plan_items', 'completed_by_role')) {
                $table->dropColumn('completed_by_role');
            }

            if (Schema::hasColumn('teaching_plan_items', 'completed_by')) {
                $table->dropColumn('completed_by');
            }
        });
    }
};
