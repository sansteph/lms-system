<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('my_spaces', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description');

            $table->enum('type', ['Idea', 'Project']);

            $table->string('blueprint_pdf')->nullable();
            $table->string('repository_link')->nullable();

            $table->string('created_by_type');
            $table->unsignedBigInteger('created_by_id');

            $table->string('status')->default('Published');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_spaces');
    }
};
