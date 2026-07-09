<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
            });
        }

        if (Schema::hasTable('institute_registration_requests')) {
            Schema::table('institute_registration_requests', function (Blueprint $table) {
                $table->dropUnique('institute_registration_requests_admin_email_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('institute_registration_requests')) {
            Schema::table('institute_registration_requests', function (Blueprint $table) {
                $table->unique('admin_email');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }
    }
};
