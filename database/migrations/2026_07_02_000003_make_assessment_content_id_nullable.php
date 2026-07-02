<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assessments', 'content_id')) {
            return;
        }

        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('content_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('assessments', 'content_id')) {
            return;
        }

        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('content_id')->nullable(false)->change();
        });
    }
};
