<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_content_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedBigInteger('stem_engineer_id');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->integer('duration_seconds')->default(0);
            $table->string('status')->default('Started');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_content_sessions');
    }
};
