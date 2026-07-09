<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Academic Templates were replaced by Teaching Plan Templates.
        // Kept as a no-op so existing migration ordering remains stable.
    }

    public function down(): void
    {
        //
    }
};
