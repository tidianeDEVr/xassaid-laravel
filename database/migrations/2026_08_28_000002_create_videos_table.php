<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->text('description');
            // Pont vers la bibliothèque audio (« Écouter le khassida complet »).
            $table->string('khassida_title')->nullable();
            // processing | pending_review | published | rejected | failed
            $table->string('status')->default('processing');
            // Original uploadé, hors public/ — supprimé après transcodage.
            $table->string('original_path')->nullable();
            // Chemin du master.m3u8 sur le disque public.
            $table->string('hls_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            // Renditions produites : [{name: "720p", bandwidth: ...}, ...]
            $table->json('renditions')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['app_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
