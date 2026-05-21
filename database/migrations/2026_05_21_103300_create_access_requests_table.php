<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('email');

            $table->string('phone');

            $table->string('role');

            $table->string('institute_name');

            $table->text('message')->nullable();

            $table->string('status')->default('Pending');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
