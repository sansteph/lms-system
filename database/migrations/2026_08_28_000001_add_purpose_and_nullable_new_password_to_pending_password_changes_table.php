<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_password_changes', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_password_changes', 'purpose')) {
                $table->string('purpose', 30)->default('change')->after('token_hash');
            }
        });

        if (Schema::hasColumn('pending_password_changes', 'new_password')) {
            Schema::table('pending_password_changes', function (Blueprint $table) {
                $table->string('new_password')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pending_password_changes')) {
            Schema::table('pending_password_changes', function (Blueprint $table) {
                if (Schema::hasColumn('pending_password_changes', 'purpose')) {
                    $table->dropColumn('purpose');
                }
            });
        }

        if (Schema::hasColumn('pending_password_changes', 'new_password')) {
            Schema::table('pending_password_changes', function (Blueprint $table) {
                $table->string('new_password')->nullable(false)->change();
            });
        }
    }
};
