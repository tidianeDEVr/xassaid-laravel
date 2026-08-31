<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Imports par lien (yt-dlp) : lus via config() et non env() pour rester
    // corrects quand la config est cachée (php artisan config:cache).
    'ytdlp' => [
        'bin' => env('YTDLP_BIN', 'yt-dlp'),
        'cookies' => env('YTDLP_COOKIES', ''),
    ],

    // Serveur de fichiers Xassaid (upload des audios/covers).
    'xassaid' => [
        'files_uri' => env('XASSAID_FILES_URI'),
        'upload_key' => env('XASSAID_UPLOAD_KEY'),
        'audio_bitrate' => (int) env('XASSAID_AUDIO_BITRATE', 96),
    ],

];
