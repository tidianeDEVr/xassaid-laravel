<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transfert des données SQLite -> MySQL/MariaDB.
 *
 * Le schéma n'est PAS créé ici : il vient des migrations
 * (`php artisan migrate --database=mysql`), pour que la cible reste
 * exactement ce que décrit le code. Cette commande ne fait que recopier les
 * lignes, identifiants préservés, dans un ordre qui respecte les clés
 * étrangères.
 *
 * Les tables de travail (cache, sessions, files d'attente) sont volontairement
 * ignorées : elles se reconstruisent seules et transporteraient des jetons
 * périmés.
 */
class SqliteToMysql extends Command
{
    protected $signature = 'db:to-mysql
        {--source=sqlite_legacy : Connexion source}
        {--target=mysql : Connexion cible}
        {--chunk=200 : Lignes par insertion}
        {--fresh : Vide les tables cibles avant la copie}
        {--dry-run : Compte seulement, n\'écrit rien}';

    protected $description = 'Recopie les données de SQLite vers MySQL (schéma déjà migré côté cible)';

    /** Ordre de copie : les parents avant les enfants. */
    private const TABLES = [
        'users',
        'roles',
        'role_user',
        'audio_categories',
        'audios',
        'files',
        'articles',
        'app_users',
        'videos',
        'video_likes',
        'video_comments',
        'video_comment_likes',
        'follows',
        'personal_access_tokens',
    ];

    public function handle(): int
    {
        $source = $this->option('source');
        $target = $this->option('target');
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $from = DB::connection($source);
        $to = DB::connection($target);

        try {
            $to->getPdo();
        } catch (\Throwable $e) {
            $this->error("Connexion « $target » injoignable : " . $e->getMessage());

            return self::FAILURE;
        }

        if (!Schema::connection($target)->hasTable('migrations')) {
            $this->error("La cible « $target » n'a pas de schéma.");
            $this->line("Lancez d'abord : php artisan migrate --database=$target --force");

            return self::FAILURE;
        }

        $this->line("Source : $source → Cible : $target" . ($dryRun ? ' (simulation)' : ''));
        $this->newLine();

        // Les tables arrivent parents d'abord, mais une contrainte croisée
        // (video_comments.parent_id) suffirait à faire échouer un lot.
        if (!$dryRun) {
            $to->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $rows = [];

        try {
            if ($this->option('fresh') && !$dryRun) {
                foreach (array_reverse(self::TABLES) as $table) {
                    if (Schema::connection($target)->hasTable($table)) {
                        $to->table($table)->truncate();
                    }
                }
                $this->line('Tables cibles vidées.');
                $this->newLine();
            }

            foreach (self::TABLES as $table) {
                if (!Schema::connection($source)->hasTable($table)) {
                    $this->line(sprintf('  %-24s absente de la source, ignorée', $table));
                    continue;
                }
                if (!Schema::connection($target)->hasTable($table)) {
                    $this->warn(sprintf('  %-24s absente de la CIBLE, ignorée', $table));
                    continue;
                }

                $total = $from->table($table)->count();
                if ($total === 0) {
                    $this->line(sprintf('  %-24s vide', $table));
                    continue;
                }

                if (!$dryRun) {
                    $from->table($table)->orderBy('id')->chunkById($chunk, function ($batch) use ($to, $table) {
                        $to->table($table)->insert(
                            array_map(fn ($row) => (array) $row, $batch->all()),
                        );
                    });
                }

                $copied = $dryRun ? 0 : $to->table($table)->count();
                $rows[$table] = [$total, $copied];

                $this->line(sprintf(
                    '  %-24s %6d ligne(s)%s',
                    $table,
                    $total,
                    $dryRun ? '' : ($copied === $total ? ' ✓' : "  ⚠ cible : $copied"),
                ));
            }
        } finally {
            if (!$dryRun) {
                $to->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        $this->newLine();

        $mismatch = array_filter($rows, fn ($c) => $c[0] !== $c[1]);
        if (!$dryRun && $mismatch !== []) {
            $this->error('Écarts de comptage : ' . implode(', ', array_keys($mismatch)));

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Simulation terminée.' : 'Transfert terminé.');

        return self::SUCCESS;
    }
}
