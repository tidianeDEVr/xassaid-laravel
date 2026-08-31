<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Import d'un audio par lien (back-office) : téléchargé par yt-dlp,
 * converti en MP3 puis uploadé sur le serveur de fichiers.
 *
 * Une ligne ne vit que le temps de l'import : supprimée en cas de succès
 * (l'Audio définitif la remplace), conservée en échec pour le bouton
 * « Réessayer ».
 */
class AudioImport extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'category_id',
        'source_url',
        'status',
        'error',
    ];

    public function category()
    {
        return $this->belongsTo(AudioCategory::class);
    }
}
