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

    /*
    |--------------------------------------------------------------------------
    | Shared Integration API — referensi lintas modul
    |--------------------------------------------------------------------------
    | Domain SHARED INTEGRATION (bukan M1, bukan M2). Dikonsumsi untuk:
    |   GET {base_url}/api/referensi/komoditas
    |   GET {base_url}/api/referensi/kelompok-tani
    |
    | Memakai token terpisah dari knowledge_api (service-to-service auth
    | antar-modul masih ditetapkan final oleh tim — lihat OPEN ISSUES).
    */
    'shared_referensi' => [
        'base_url' => env('SHARED_API_BASE_URL'),
        'token' => env('SHARED_API_TOKEN'),
        'timeout' => env('SHARED_API_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modul Diagnosis — Mahasiswa 2
    |--------------------------------------------------------------------------
    | Rate limit per user per menit untuk endpoint POST /api/diagnosis.
    | Diagnosis memicu panggilan Knowledge API + perhitungan + penyimpanan,
    | sehingga perlu dibatasi agar tidak mudah dibanjiri (DoS/spam).
    */
    'diagnosis' => [
        'rate_limit_per_minute' => (int) env('DIAGNOSIS_RATE_LIMIT_PER_MINUTE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permohonan Penanganan — Mahasiswa 2
    |--------------------------------------------------------------------------
    | Rate limit pembuatan permohonan per user per menit (POST
    | /api/permohonan) — membatasi beban upload evidence/storage.
    */
    'permohonan' => [
        'rate_limit_per_minute' => (int) env('PERMOHONAN_RATE_LIMIT_PER_MINUTE', 10),
    ],

];
