<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien d'origine des vidéos importées par le back-office : la description
 * étant écrasée par le titre après téléchargement, ce champ est le seul
 * moyen de relancer un import en échec (bouton « Réessayer »).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('source_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('source_url');
        });
    }
};
