<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'support@tinkedge.com')
            ->where('role', 'Manager')
            ->update([
                'name' => 'Manager',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep the manager display name as-is on rollback.
    }
};
