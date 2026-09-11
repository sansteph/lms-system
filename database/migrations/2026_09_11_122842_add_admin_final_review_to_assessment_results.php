<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_results', 'admin_reviewed_by')) {
                $table->unsignedBigInteger('admin_reviewed_by')->nullable()->after('evaluated_at');
            }

            if (!Schema::hasColumn('assessment_results', 'admin_reviewed_at')) {
                $table->timestamp('admin_reviewed_at')->nullable()->after('admin_reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_results', 'admin_reviewed_at')) {
                $table->dropColumn('admin_reviewed_at');
            }

            if (Schema::hasColumn('assessment_results', 'admin_reviewed_by')) {
                $table->dropColumn('admin_reviewed_by');
            }
        });
    }
};
