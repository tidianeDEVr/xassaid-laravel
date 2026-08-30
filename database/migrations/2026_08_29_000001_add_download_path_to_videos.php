<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MP4 téléchargeable (remux de la meilleure rendition HLS) : le flux HLS ne
// peut pas être enregistré tel quel dans la galerie du téléphone.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('download_path')->nullable()->after('poster_path');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('download_path');
        });
    }
};
