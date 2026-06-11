<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {

            $table->unsignedBigInteger('content_id')
                ->nullable()
                ->after('class_teacher');

        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {

            $table->dropColumn('content_id');

        });
    }
};