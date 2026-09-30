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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'shortcut' => [
        'token' => env('ADMIN_SHORTCUT_TOKEN'),
    ],
    'gemini' => [
        // Jaring pengaman terakhir. Sumber utama tiap fitur ada di tabel
        // `settings` (chatbot_api_key / report_api_key / broadcast_api_key);
        // key di sini hanya dipakai kalau semuanya kosong.
        'key' => env('GEMINI_API_KEY'),
        'report_key' => env('GEMINI_REPORT_API_KEY'),
        'broadcast_key' => env('GEMINI_BROADCAST_API_KEY'),
    ],
    'whatsapp' => [
        'url' => env('WHATSAPP_BOT_URL', 'http://localhost:3001'),
        'api_key' => env('WHATSAPP_BOT_API_KEY', 'rentspace_secret_wa_token_2026'),
    ],
    'instagram' => [
        // Sumber utama tetap tabel `settings` (ig_access_token / ig_user_id)
        // supaya bisa diisi dari panel admin. Env ini hanya fallback.
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'user_id' => env('INSTAGRAM_USER_ID'),
        'graph_version' => env('INSTAGRAM_GRAPH_VERSION', 'v21.0'),
    ],
];
