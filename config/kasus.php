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
    | State machine transisi (current_status → allowed next status).
    |
    | 'diterima' hanya bertransisi via penugasan POPT (ditugaskan).
    | 'selesai' bersifat terminal: tidak ada transisi keluar.
    |--------------------------------------------------------------------------
    */
    'transitions' => [
        'diterima' => ['ditugaskan'],
        'ditugaskan' => ['sedang_direview'],
        'sedang_direview' => ['ditunda', 'siap_dieksekusi', 'selesai'],
        'ditunda' => ['sedang_direview', 'selesai'],
        'siap_dieksekusi' => ['dalam_pelaksanaan', 'selesai'],
        'dalam_pelaksanaan' => ['selesai'],
        'selesai' => [],
    ],

];
