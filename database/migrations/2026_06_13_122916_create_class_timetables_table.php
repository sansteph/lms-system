<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_timetables', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('class_id');

            $table->date('session_date');

            $table->string('day');

            $table->enum('day_type', [
                'Working Day',
                'Holiday'
            ])->default('Working Day');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_timetables');
    }
};
