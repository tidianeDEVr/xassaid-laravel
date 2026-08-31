<?php

namespace App\Support;

/**
 * Échec d'un téléchargement yt-dlp : le message est le motif lisible destiné
 * au back-office, $stderr la sortie brute pour les logs.
 */
class YtDlpException extends \RuntimeException
{
    public function __construct(
        string $reason,
        public readonly string $stderr = '',
    ) {
        parent::__construct($reason);
    }

    /** Dernières lignes de stderr, pour les logs. */
    public function stderrTail(): string
    {
        return implode(' | ', array_slice(
            preg_split('/\r?\n/', trim($this->stderr)),
            -3,
        ));
    }
}
