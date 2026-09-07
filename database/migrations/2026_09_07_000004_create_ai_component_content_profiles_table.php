<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_component_content_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->unique()->constrained('contents')->cascadeOnDelete();
            $table->string('component_key')->index();
            $table->string('component_label');
            $table->boolean('is_practical')->default(false)->index();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->json('evidence')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_component_content_profiles');
    }
};
