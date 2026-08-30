<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Utilisateurs de l'application mobile (feed vidéo). Table séparée des
// `users` du back-office : pas d'email, identité = pseudo unique.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('display_name');
            $table->string('avatar_path')->nullable();
            $table->string('password');
            $table->boolean('is_certified')->default(false);
            $table->unsignedInteger('videos_count')->default(0);
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
