<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_contents') && Schema::hasColumn('course_contents', 'library_content_id')) {
            try {
                Schema::table('course_contents', function (Blueprint $table) {
                    $table->dropUnique(['course_id', 'library_content_id']);
                });
            } catch (\Throwable $exception) {
                //
            }
        }

        $this->dropColumnIfExists('class_content_sessions', 'library_content_id');
        $this->dropColumnIfExists('teaching_plan_items', 'library_content_id');
        $this->dropColumnIfExists('teaching_plans', 'library_content_id');
        $this->dropColumnIfExists('contents', 'library_content_id');
        $this->dropColumnIfExists('course_contents', 'library_content_id');

        Schema::dropIfExists('content_library_items');
    }

    public function down(): void
    {
        //
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
    }
};
