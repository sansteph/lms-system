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
    Schema::create('assessments', function (Blueprint $table) {
        $table->id();
        $table->string('assessment_title');
        $table->string('assessment_type');
        $table->string('assigned_class');
        $table->integer('total_marks');
        $table->string('duration');
        $table->string('question_paper_type')->nullable();
        $table->string('file_path')->nullable();
        $table->boolean('status')->default(1);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
