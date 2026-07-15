<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'designation')) {
                $table->string('designation')->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'joined_on')) {
                $table->date('joined_on')->nullable()->after('designation');
            }

            if (!Schema::hasColumn('users', 'linkedin_url')) {
                $table->string('linkedin_url')->nullable()->after('joined_on');
            }

            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('linkedin_url');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'linkedin_url')) {
                $table->string('linkedin_url')->nullable()->after('profile_completed');
            }

            if (!Schema::hasColumn('students', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('linkedin_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['profile_image', 'linkedin_url', 'joined_on', 'designation'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('students', function (Blueprint $table) {
            foreach (['profile_image', 'linkedin_url'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
