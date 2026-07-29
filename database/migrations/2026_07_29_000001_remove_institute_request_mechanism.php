<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('institute_registration_requests');
        Schema::dropIfExists('access_requests');
    }

    public function down(): void
    {
        // Request-based institute onboarding has been intentionally removed.
    }
};
