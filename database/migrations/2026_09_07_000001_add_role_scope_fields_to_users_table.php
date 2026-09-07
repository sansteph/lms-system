<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'institute')) {
                $table->string('institute')->nullable()->after('status');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('institute');
            }
        });
    }

    public function down(): void
    {
        // These columns are already part of the live application contract.
        // Keep rollback non-destructive for databases where they existed before this migration.
    }
};
