<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Alur Status Kasus Penanganan (Mahasiswa 2)
|--------------------------------------------------------------------------
| Satu tempat pusat untuk STATE MACHINE status kasus (kontrak §13-17).
| Status di sini MUST match nilai kolom kasus_penanganan.current_status
| dan riwayat_status_penanganan.status.
|
| Alur normal:
|   diterima ─(assign POPT)──▶ ditugaskan ─▶ sedang_direview
|        ─▶ ditunda | siap_dieksekusi ─▶ dalam_pelaksanaan ─▶ selesai
|
| `transitions` memetakan status SAAT INI → daftar status yang boleh dituju.
| Validasi dijalankan terpusat oleh StatusTransitionService sehingga
| transisi ilegal (mis. melompat/mundur dari selesai) tidak mungkin.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Daftar status yang dikenali (referensi validasi request).
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'diterima',
        'ditugaskan',
        'sedang_direview',
        'ditunda',
        'siap_dieksekusi',
        'dalam_pelaksanaan',
        'selesai',
    ],

    /*
    |--------------------------------------------------------------------------
    | Label tampilan status (sumber tunggal untuk badge/filter/timeline).
    |--------------------------------------------------------------------------
    | Blade memakai komponen x-kasus.status-badge / x-kasus.status-options
    | agar tidak ada lagi map label yang di-hardcode per view.
    */
    'labels' => [
        'diterima' => 'Diterima',
        'ditugaskan' => 'Ditugaskan',
        'sedang_direview' => 'Sedang Direview',
        'ditunda' => 'Ditunda',
        'siap_dieksekusi' => 'Siap Dieksekusi',
        'dalam_pelaksanaan' => 'Dalam Pelaksanaan',
        'selesai' => 'Selesai',
    ],

    /*
    |--------------------------------------------------------------------------
    | Label + gaya badge STATUS PERMOHONAN (permohonan_penanganan.status).
    |--------------------------------------------------------------------------
    | Sumber tunggal untuk komponen x-kasus.request-status-badge agar warna dan
    | ikon konsisten di halaman Daftar Permohonan dan Detail Permohonan Poktan.
    */
    'request_labels' => [
        'diajukan' => 'Diajukan',
        'sedang_direview' => 'Sedang Direview',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
    ],

    'request_status_badge' => [
        'diajukan' => [
            'bg' => 'bg-blue-50 text-blue-700',
            'icon' => 'M12 5v14M5 12h14',
        ],
        'sedang_direview' => [
            'bg' => 'bg-amber-50 text-amber-700',
            'icon' => 'M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
        ],
        'diterima' => [
            'bg' => 'bg-[#e8f4ed] text-[#176b45]',
            'icon' => 'M20 6 9 17l-5-5',
        ],
        'ditolak' => [
            'bg' => 'bg-red-50 text-red-700',
            'icon' => 'M18 6 6 18M6 6l12 12',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | State machine transisi (current_status → allowed next status).
    |
    | 'diterima' hanya bertransisi via penugasan POPT (ditugaskan).
    | 'selesai' bersifat terminal: tidak ada transisi keluar.
    |--------------------------------------------------------------------------
    */
    'transitions' => [
        'diterima' => ['ditugaskan'],
        'ditugaskan' => ['sedang_direview'],
        'sedang_direview' => ['ditunda', 'siap_dieksekusi'],
        'ditunda' => ['sedang_direview'],
        'siap_dieksekusi' => ['dalam_pelaksanaan'],
        'dalam_pelaksanaan' => ['selesai'],
        'selesai' => [],
    ],

];
