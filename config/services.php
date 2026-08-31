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
    |   GET {base_url}/api/komoditas?start=0&limit={page_size}
    |   GET {base_url}/api/kelompok-tani?start=0&limit={page_size}
    |
    | Memakai token terpisah dari knowledge_api (service-to-service auth
    | antar-modul masih ditetapkan final oleh tim — lihat OPEN ISSUES).
    */
    'shared_referensi' => [
        'base_url' => env('SHARED_API_BASE_URL'),
        'token' => env('SHARED_API_TOKEN'),
        'timeout' => env('SHARED_API_TIMEOUT', 30),
        'page_size' => env('SHARED_API_PAGE_SIZE', 50),
        'max_pages' => env('SHARED_API_MAX_PAGES', 250),
        'user_agent' => env('SHARED_API_USER_AGENT', 'SIPAKARBUN/1.0'),
        'page_delay_ms' => env('SHARED_API_PAGE_DELAY_MS', 750),
        'rate_limit_retries' => env('SHARED_API_RATE_LIMIT_RETRIES', 3),
        'rate_limit_backoff_ms' => env('SHARED_API_RATE_LIMIT_BACKOFF_MS', 60000),
        'source_exhaustion_warning_ratio' => env('SHARED_API_SOURCE_EXHAUSTION_WARNING_RATIO', 0.90),
    ],

    // Local/UAT only. Keep the actual values in the ignored .env file.
    'uat' => [
        'accounts' => [
            'operator_uptd' => [
                'password' => env('SIPAKARBUN_UAT_OPERATOR_PASSWORD'),
            ],
            'popt' => [
                'password' => env('SIPAKARBUN_UAT_POPT_PASSWORD'),
            ],
            'poktan' => [
                'password' => env('SIPAKARBUN_UAT_POKTAN_PASSWORD'),
            ],
            'pimpinan' => [
                'password' => env('SIPAKARBUN_UAT_PIMPINAN_PASSWORD'),
            ],
        ],
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
