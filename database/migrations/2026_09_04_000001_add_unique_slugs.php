<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index uniques sur les slugs. Lancer d'abord `php artisan slugs:dedupe --apply`
 * sinon la migration échoue sur les doublons existants.
 */
return new class extends Migration
{
    private array $tables = ['audios', 'audio_categories', 'articles', 'files'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unique('slug', "{$table}_slug_unique");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("{$table}_slug_unique");
            });
        }
    }
};
