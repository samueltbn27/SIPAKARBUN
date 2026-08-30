<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reference Disbun disinkronkan ke tabel lokal sekali sehari. Command
// melakukan fetch lengkap sebelum transaksi sehingga kegagalan jaringan,
// malformed page, atau conflicting duplicate tetap mempertahankan last-good.
Schedule::command('disbun:sync-references')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(120);
