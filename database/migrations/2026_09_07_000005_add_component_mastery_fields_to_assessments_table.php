<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'component_key')) {
                $table->string('component_key')->nullable()->after('assessment_category')->index();
            }

            if (!Schema::hasColumn('assessments', 'component_label')) {
                $table->string('component_label')->nullable()->after('component_key');
            }

            if (!Schema::hasColumn('assessments', 'certificate_eligible')) {
                $table->boolean('certificate_eligible')->default(false)->after('component_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $columns = [];

            foreach (['component_key', 'component_label', 'certificate_eligible'] as $column) {
                if (Schema::hasColumn('assessments', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
