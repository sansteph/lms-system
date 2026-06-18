<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_timetables', function (Blueprint $table) {
            $table->unsignedBigInteger('content_id')->nullable()->after('class_id');

            $table->foreign('content_id')
                ->references('id')
                ->on('contents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_timetables', function (Blueprint $table) {
            $table->dropForeign(['content_id']);
            $table->dropColumn('content_id');
        });
    }
};
