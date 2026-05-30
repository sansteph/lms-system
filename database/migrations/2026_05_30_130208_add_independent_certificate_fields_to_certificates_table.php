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
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('certificate_type')->default('Student')->after('id');
            $table->unsignedBigInteger('course_id')->nullable()->after('student_id');
            $table->unsignedBigInteger('independent_learner_id')->nullable()->after('course_id');

            $table->unsignedBigInteger('student_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_type',
                'course_id',
                'independent_learner_id',
            ]);
        });
    }
};
