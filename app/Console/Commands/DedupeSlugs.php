<?php

namespace App\Console\Commands;

use App\Models\SlugRedirect;
use App\Support\Slugger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renomme les slugs dupliqués (audios, catégories, articles, fichiers).
 *
 *   php artisan slugs:dedupe            # simulation : liste les renommages
 *   php artisan slugs:dedupe --apply    # applique
 *
 * Pour chaque slug en double, la ligne la plus ancienne garde son slug ; les
 * autres deviennent « titre-catégorie » pour les audios (suffixe = slug de la
 * catégorie), puis « …-2 », « …-3 » si nécessaire.
 */
class DedupeSlugs extends Command
{
    protected $signature = 'slugs:dedupe {--apply : Écrit les changements (sinon simulation)}';

    protected $description = 'Rend uniques les slugs dupliqués des audios, catégories, articles et fichiers';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $total = 0;
        foreach (['audios', 'audio_categories', 'articles', 'files'] as $table) {
            $total += $this->dedupe($table, $apply);
        }
        $this->newLine();
        $this->info($apply ? "$total slug(s) renommé(s)." : "$total slug(s) à renommer (simulation, ajouter --apply).");

        return self::SUCCESS;
    }

    private function dedupe(string $table, bool $apply): int
    {
        $dups = DB::table($table)->select('slug', DB::raw('COUNT(*) AS n'))
            ->groupBy('slug')->having('n', '>', 1)->pluck('slug');
        $this->line("<comment>$table</comment> : {$dups->count()} slug(s) dupliqué(s)");
        $count = 0;
        foreach ($dups as $slug) {
            $rows = DB::table($table)->where('slug', $slug)->orderBy('id')->get();
            foreach ($rows->slice(1) as $row) { // la première ligne garde le slug
                $suffix = null;
                if ($table === 'audios' && $row->category_id) {
                    $suffix = DB::table('audio_categories')->where('id', $row->category_id)->value('slug');
                }
                // Le titre seul est déjà pris (par la ligne conservée) : on force le suffixe
                $base = Slugger::base($row->title) ?: $slug;
                $new = $table === 'audios'
                    ? Slugger::forAudio($row->title, $suffix, $row->id, $slug)
                    : Slugger::unique($base, $table, $row->id, null, $slug);
                $label = $table === 'audios' && $suffix ? " [$suffix]" : '';
                $this->line("  #{$row->id} {$row->title}{$label} : {$row->slug} -> <info>{$new}</info>");
                if ($apply) {
                    DB::table($table)->where('id', $row->id)->update(['slug' => $new]);
                    if (Schema::hasTable('slug_redirects')) {
                        SlugRedirect::remember($table, $row->slug, $new);
                    }
                }
                $count++;
            }
        }

        return $count;
    }
}
