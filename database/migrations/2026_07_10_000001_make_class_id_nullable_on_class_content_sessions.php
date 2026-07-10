<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_content_sessions') ||
            !Schema::hasColumn('class_content_sessions', 'class_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE class_content_sessions MODIFY class_id BIGINT UNSIGNED NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE class_content_sessions ALTER COLUMN class_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        //
    }
};
