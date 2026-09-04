<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Renommages faits par `slugs:dedupe --apply` le 4 septembre 2026, avant l'existence de la table. */
    private array $seed = [
        ['audios', 'ikfini', 'ikfini-baye-madieye-diop'],
        ['audios', 'jazbou', 'jazbou-serigne-abdou-diokhan'],
        ['audios', 'jazbu', 'jazbu-serigne-mayib-gueye'],
        ['audios', 'mawahibou', 'mawahibou-treviso-italie'],
    ];

    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 40);
            $table->string('old_slug');
            $table->string('new_slug');
            $table->timestamps();
            $table->unique(['entity', 'old_slug']);
            $table->index(['entity', 'new_slug']);
        });

        // Ces anciens slugs sont encore portés par la ligne conservée (la plus
        // ancienne) : on ne redirige que si l'adresse n'existe plus.
        foreach ($this->seed as [$entity, $old, $new]) {
            $stillUsed = DB::table($entity)->where('slug', $old)->exists();
            $target = DB::table($entity)->where('slug', $new)->exists();
            if (! $stillUsed && $target) {
                DB::table('slug_redirects')->insert([
                    'entity' => $entity, 'old_slug' => $old, 'new_slug' => $new,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
