<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('target')->default('all');
            $table->string('institute')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['target', 'institute', 'status']);
            $table->index(['starts_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_notifications');
    }
};
