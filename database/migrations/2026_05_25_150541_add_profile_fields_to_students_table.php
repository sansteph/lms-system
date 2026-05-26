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
        Schema::table('students', function (Blueprint $table) {

            $table->string('email')->nullable()->after('name');

            $table->string('guardian_name')->nullable();
            $table->string('guardian_contact')->nullable();

            $table->boolean('is_robotics_club_member')
                ->default(false);

            $table->boolean('profile_completed')
                ->default(false);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            //
        });
    }
};
