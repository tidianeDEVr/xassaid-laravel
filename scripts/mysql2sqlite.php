<?php
/**
 * Script de conversion MySQL vers SQLite
 * Extrait les INSERT du dump MySQL et les importe dans SQLite
 *
 * Usage: php scripts/mysql2sqlite.php ~/Downloads/xassaid.sql
 */

$dumpFile = $argv[1] ?? null;

if (!$dumpFile || !file_exists($dumpFile)) {
    echo "Usage: php scripts/mysql2sqlite.php <chemin_dump_mysql.sql>\n";
    exit(1);
}

$dbPath = __DIR__ . '/../database/database.sqlite';

if (!file_exists($dbPath)) {
    echo "Erreur: La base SQLite n'existe pas. Exécutez d'abord: php artisan migrate:fresh\n";
    exit(1);
}

echo "Lecture du dump MySQL: $dumpFile\n";
$lines = file($dumpFile, FILE_IGNORE_NEW_LINES);

echo "Connexion à SQLite: $dbPath\n";
$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Désactiver les foreign keys pendant l'import
$db->exec('PRAGMA foreign_keys = OFF');

// Tables à importer (dans l'ordre pour respecter les dépendances)
$tables = [
    'roles',
    'users',
    'role_user',
    'audio_categories',
    'audios',
    'articles',
    'files'
];

// Mapping des colonnes du dump vers les colonnes SQLite (ordre Laravel)
$columnMappings = [
    'files' => [
        'dump_order' => ['id', 'created_at', 'updated_at', 'slug', 'title', 'pathToFile'],
        'sqlite_order' => ['id', 'slug', 'title', 'pathToFile', 'created_at', 'updated_at']
    ],
    'audios' => [
        'dump_order' => ['id', 'created_at', 'updated_at', 'slug', 'title', 'pathToFile', 'category_id'],
        'sqlite_order' => ['id', 'slug', 'title', 'pathToFile', 'created_at', 'updated_at', 'category_id']
    ]
];

$totalImported = 0;

foreach ($tables as $table) {
    echo "\nTraitement de la table: $table\n";

    $count = 0;
    $inInsert = false;
    $currentColumns = '';
    $buffer = '';

    foreach ($lines as $line) {
        // Détecter le début d'un INSERT pour cette table
        if (preg_match("/^INSERT INTO `$table` \(([^)]+)\) VALUES$/", $line, $match)) {
            $inInsert = true;
            $currentColumns = str_replace('`', '', $match[1]);
            $buffer = '';
            continue;
        }

        if ($inInsert) {
            // Fin de l'INSERT (ligne vide ou nouveau commentaire)
            if (empty(trim($line)) || strpos($line, '--') === 0 || strpos($line, 'INSERT INTO') === 0) {
                $inInsert = false;
                continue;
            }

            // Ligne de valeurs
            $line = trim($line);

            // Supprimer la virgule ou point-virgule final
            $line = rtrim($line, ',;');

            // Parser les valeurs
            if (preg_match('/^\((.+)\)$/', $line, $match)) {
                $valuesStr = $match[1];

                // Construire l'INSERT SQLite
                $sql = "INSERT INTO $table ($currentColumns) VALUES ($valuesStr)";

                // Convertir les échappements MySQL vers SQLite
                $sql = str_replace("\\'", "''", $sql);
                $sql = str_replace('\\"', '"', $sql);
                $sql = str_replace('\\n', "\n", $sql);
                $sql = str_replace('\\r', "\r", $sql);

                try {
                    $db->exec($sql);
                    $count++;
                } catch (PDOException $e) {
                    echo "  Erreur: " . $e->getMessage() . "\n";
                    echo "  SQL: " . substr($sql, 0, 150) . "...\n";
                }
            }
        }
    }

    echo "  -> $count enregistrements importés\n";
    $totalImported += $count;
}

// Réactiver les foreign keys
$db->exec('PRAGMA foreign_keys = ON');

echo "\n========================================\n";
echo "Import terminé: $totalImported enregistrements au total\n";
echo "========================================\n";
