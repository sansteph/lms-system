<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('users')->where('email', 'support@tinkedge.com')->first();
        $payload = [
            'name' => 'Manager',
            'email' => 'support@tinkedge.com',
            'role' => 'Manager',
            'password' => Hash::make('Manager@TinkEdgeLMS26'),
            'status' => 1,
            'institute' => null,
            'phone' => null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($payload);
            return;
        }

        $managerId = 'MGR0001';
        $counter = 1;

        while (DB::table('users')->where('user_id', $managerId)->exists()) {
            $counter++;
            $managerId = 'MGR' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
        }

        DB::table('users')->insert($payload + [
            'user_id' => $managerId,
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep the predefined manager account intact on rollback.
    }
};
