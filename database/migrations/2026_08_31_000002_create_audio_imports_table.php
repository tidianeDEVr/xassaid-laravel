<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi des imports d'audios par lien (yt-dlp -> MP3 -> upload).
 *
 * Un import réussi crée l'Audio définitif puis supprime sa ligne ici : la
 * table ne contient que les imports en cours ou en échec, affichés sur la
 * page /audios avec un bouton « Réessayer ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('audio_categories')
                ->cascadeOnDelete();
            $table->string('source_url', 500);
            $table->string('status', 20)->default('processing');
            $table->string('error', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_imports');
    }
};
