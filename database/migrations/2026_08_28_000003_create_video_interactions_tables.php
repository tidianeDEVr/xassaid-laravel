<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Likes, commentaires (un seul niveau de réponse) et abonnements du feed vidéo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['video_id', 'app_user_id']);
        });

        Schema::create('video_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            // Un seul niveau : parent_id pointe toujours un commentaire racine.
            $table->foreignId('parent_id')->nullable()->constrained('video_comments')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index(['video_id', 'parent_id']);
        });

        Schema::create('video_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_comment_id')->constrained('video_comments')->cascadeOnDelete();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['video_comment_id', 'app_user_id']);
        });

        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained('app_users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'followed_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
        Schema::dropIfExists('video_comment_likes');
        Schema::dropIfExists('video_comments');
        Schema::dropIfExists('video_likes');
    }
};
