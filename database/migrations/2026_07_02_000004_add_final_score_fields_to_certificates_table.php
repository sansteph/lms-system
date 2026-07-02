<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'final_score')) {
                $table->decimal('final_score', 5, 2)->nullable()->after('badge_count');
            }

            if (!Schema::hasColumn('certificates', 'final_grade')) {
                $table->string('final_grade')->nullable()->after('final_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'final_grade')) {
                $table->dropColumn('final_grade');
            }

            if (Schema::hasColumn('certificates', 'final_score')) {
                $table->dropColumn('final_score');
            }
        });
    }
};
