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

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management API — Mahasiswa 1 (modul Knowledge)
    |--------------------------------------------------------------------------
    | Dikonsumsi modul Diagnosis (Mahasiswa 2). Pasangan endpoint:
    |   GET {base_url}/api/penyakit?komoditas_id={id}
    |   GET {base_url}/api/gejala?komoditas_id={id}
    |
    | Butuh header Authorization: Bearer {token} (token Sanctum). Token tidak
    | di-hardcode — diambil dari .env, lihat .env.example.
    */
    'knowledge_api' => [
        'base_url' => env('KNOWLEDGE_API_BASE_URL'),
        'token' => env('KNOWLEDGE_API_TOKEN'),
        'timeout' => env('KNOWLEDGE_API_TIMEOUT', 5),
    ],

];
