<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('academic_deployments');
        Schema::dropIfExists('academic_template_contents');
        Schema::dropIfExists('academic_package_templates');
        Schema::dropIfExists('academic_templates');
        Schema::dropIfExists('academic_packages');
    }

    public function down(): void
    {
        //
    }
};
