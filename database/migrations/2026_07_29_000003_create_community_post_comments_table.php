<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->string('commenter_type');
            $table->unsignedBigInteger('commenter_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['commenter_type', 'commenter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_comments');
    }
};
