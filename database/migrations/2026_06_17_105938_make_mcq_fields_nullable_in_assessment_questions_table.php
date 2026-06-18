<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->text('option_a')->nullable()->change();
            $table->text('option_b')->nullable()->change();
            $table->text('option_c')->nullable()->change();
            $table->text('option_d')->nullable()->change();
            $table->text('correct_answer')->nullable()->change();

            $table->text('short_answer')->nullable()->change();
            $table->text('long_answer')->nullable()->change();
            $table->text('explanation')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->text('option_a')->nullable(false)->change();
            $table->text('option_b')->nullable(false)->change();
            $table->text('option_c')->nullable(false)->change();
            $table->text('option_d')->nullable(false)->change();
            $table->text('correct_answer')->nullable(false)->change();
        });
    }
};