<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'class_teacher')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->string('class_teacher')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'class_teacher')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->string('class_teacher')->nullable(false)->change();
            });
        }
    }
};
