<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('post_type')->default('General Post');
            $table->string('image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('author_type');
            $table->unsignedBigInteger('author_id');
            $table->string('institute')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['institute', 'status']);
            $table->index(['author_type', 'author_id']);
        });

        Schema::create('community_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->string('liker_type');
            $table->unsignedBigInteger('liker_id');
            $table->timestamps();

            $table->unique(['community_post_id', 'liker_type', 'liker_id'], 'community_post_likes_unique_actor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_likes');
        Schema::dropIfExists('community_posts');
    }
};
