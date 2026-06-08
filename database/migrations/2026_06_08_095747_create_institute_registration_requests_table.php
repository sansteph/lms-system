<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institute_registration_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_id')->unique();

            $table->string('admin_name');

            $table->string('admin_email')->unique();

            $table->string('phone');

            $table->string('institute_name');

            $table->string('location');

            $table->string('status')->default('Pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_registration_requests');
    }
};
