<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Copie cohérente de la base SQLite, WAL compris.
 *
 * `scp database.sqlite` seul ne suffit pas : la base tourne en mode WAL, les
 * transactions récentes vivent dans `database.sqlite-wal` et manqueraient à
 * la copie. `VACUUM INTO` écrit un fichier complet et cohérent, sans
 * interrompre le service.
 */
class DbSnapshot extends Command
{
    protected $signature = 'db:snapshot {--path=database/snapshot.sqlite : Fichier de sortie, relatif à la racine du projet}';

    protected $description = 'Écrit une copie cohérente de la base SQLite (WAL inclus)';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->error('La connexion par défaut n\'est pas SQLite.');

            return self::FAILURE;
        }

        $target = base_path($this->option('path'));
        if (is_file($target)) {
            unlink($target);
        }

        DB::statement('VACUUM INTO ' . DB::connection()->getPdo()->quote($target));

        $this->info(sprintf(
            'Snapshot écrit : %s (%s Mo)',
            $target,
            number_format(filesize($target) / 1048576, 1),
        ));

        return self::SUCCESS;
    }
}
