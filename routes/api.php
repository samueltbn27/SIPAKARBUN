<?php

use App\Http\Controllers\AturanCfController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\GejalaController;
use App\Http\Controllers\KasusController;
use App\Http\Controllers\KnowledgeApiController;
use App\Http\Controllers\OperatorPermohonanController;
use App\Http\Controllers\PenyakitController;
use App\Http\Controllers\PermohonanController;
use App\Http\Controllers\PoptController;
use App\Http\Controllers\SolusiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kontrak API publik untuk Mahasiswa 2 (Diagnosis)
|--------------------------------------------------------------------------
| Path polos, auth:sanctum saja (tanpa cek role) — yang memanggil adalah
| sistem M2, bukan manusia. Hanya mengekspos knowledge is_active=true.
| Lihat dokumen: KONTRAK_API_M1_ke_M2.md
*/
Route::middleware(['auth:sanctum'])
    ->group(function (): void {
        Route::get('/penyakit', [KnowledgeApiController::class, 'penyakit']);
        Route::get('/gejala', [KnowledgeApiController::class, 'gejala']);
    });

/*
|--------------------------------------------------------------------------
| Diagnosis (Mahasiswa 2)
|--------------------------------------------------------------------------
| POST /api/diagnosis        — jalankan diagnosis baru (transaction).
| GET  /api/diagnosis        — histori diagnosis milik user yang login.
| GET  /api/diagnosis/{id}   — detail diagnosis milik user yang login.
|
| POST dibatasi rate limit per user (throttle:diagnosis) supaya tidak
| memicu spam panggilan Knowledge API / DoS.
*/
Route::middleware(['auth:sanctum'])
    ->prefix('diagnosis')
    ->group(function (): void {
        Route::post('/', [DiagnosisController::class, 'store'])
            ->middleware('throttle:diagnosis');
        Route::get('/', [DiagnosisController::class, 'index']);
        Route::get('/{id}', [DiagnosisController::class, 'show'])
            ->whereNumber('id');
    });

/*
|--------------------------------------------------------------------------
| Permohonan Penanganan (Poktan) — kontrak M2 §9
|--------------------------------------------------------------------------
| POST /api/permohonan           — ajukan permohonan penanganan.
| GET  /api/permohonan           — daftar permohonan milik user (pemohon).
| GET  /api/permohonan/{id}      — detail permohonan milik user (pemohon).
|
| Pemohon HANYA bisa melihat permohonan yang dibuatnya sendiri
| (created_by). Perlakuan Operator UPTD ada di group /api/operator.
*/
Route::middleware(['auth:sanctum'])
    ->prefix('permohonan')
    ->group(function (): void {
        Route::post('/', [PermohonanController::class, 'store'])
            ->middleware('throttle:permohonan');
        Route::get('/', [PermohonanController::class, 'index']);
        Route::get('/{id}', [PermohonanController::class, 'show'])
            ->whereNumber('id');
    });

/*
|--------------------------------------------------------------------------
| Operator UPTD — Review & Keputusan Permohonan (kontrak M2 §11-12)
|--------------------------------------------------------------------------
| GET  /api/operator/permohonan                  — daftar permohonan.
| GET  /api/operator/permohonan/{id}             — detail + keputusan + kasus.
| POST /api/operator/permohonan/{id}/review      — mulai mereview.
| POST /api/operator/permohonan/{id}/accept      — terima → lahir kasus.
| POST /api/operator/permohonan/{id}/reject      — tolak (alasan wajib).
|
| HANYA role operator_uptd (Operator UPTD) yang boleh mengakses.
| Transisi status divalidasi di PermohonanService; setiap keputusan
| tercatat append-only di keputusan_permohonan.
*/
Route::middleware(['auth:sanctum', 'role:operator_uptd'])
    ->prefix('operator/permohonan')
    ->group(function (): void {
        Route::get('/', [OperatorPermohonanController::class, 'index']);
        Route::get('/{id}', [OperatorPermohonanController::class, 'show'])
            ->whereNumber('id');
        Route::post('/{id}/review', [OperatorPermohonanController::class, 'review'])
            ->whereNumber('id');
        Route::post('/{id}/accept', [OperatorPermohonanController::class, 'accept'])
            ->whereNumber('id');
        Route::post('/{id}/reject', [OperatorPermohonanController::class, 'reject'])
            ->whereNumber('id');
    });

/*
|--------------------------------------------------------------------------
| Kasus Penanganan — Operator UPTD / Admin (kontrak M2 §13)
|--------------------------------------------------------------------------
| GET  /api/kasus                     — daftar kasus (+ filter status).
| GET  /api/kasus/{id}                — detail kasus lengkap.
| GET  /api/kasus/{id}/history        — riwayat status (append-only).
| POST /api/kasus/{id}/assign-popt    — tetapkan POPT (role popt + aktif).
|
| Status kasus dikelola state machine di config/kasus.php dan hanya bisa
| berubah lewat StatusTransitionService (riwayat selalu tercatat).
*/
Route::middleware(['auth:sanctum', 'role:admin|operator_uptd'])
    ->prefix('kasus')
    ->group(function (): void {
        Route::get('/', [KasusController::class, 'index']);
        Route::get('/{id}', [KasusController::class, 'show'])
            ->whereNumber('id');
        Route::get('/{id}/history', [KasusController::class, 'history'])
            ->whereNumber('id');
        Route::post('/{id}/assign-popt', [KasusController::class, 'assignPopt'])
            ->whereNumber('id');
    });

/*
|--------------------------------------------------------------------------
| POPT — Penugasan & Status Kasus (kontrak M2 §16-17)
|--------------------------------------------------------------------------
| GET  /api/popt/penugasan              — kasus yang sedang ditugaskan.
| GET  /api/popt/kasus/{id}             — detail kasus milik POPT ini.
| POST /api/popt/kasus/{id}/status      — perbarui status (state machine).
|
| HANYA role popt. Akses lintas POPT (kasus bukan penugasan aktif user)
| ditolak 403 di PoptController.
*/
Route::middleware(['auth:sanctum', 'role:popt'])
    ->prefix('popt')
    ->group(function (): void {
        Route::get('/penugasan', [PoptController::class, 'index']);
        Route::get('/kasus/{id}', [PoptController::class, 'show'])
            ->whereNumber('id');
        Route::post('/kasus/{id}/status', [PoptController::class, 'updateStatus'])
            ->whereNumber('id');
    });

/*
|--------------------------------------------------------------------------
| CRUD internal Admin/Pakar
|--------------------------------------------------------------------------
| Wajib login (auth:sanctum) + role admin atau popt.
*/
Route::middleware(['auth:sanctum', 'role:admin|popt'])
    ->prefix('admin')
    ->group(function (): void {
        Route::apiResource('penyakit', PenyakitController::class);
        Route::apiResource('gejala', GejalaController::class);
        Route::apiResource('solusi', SolusiController::class);
        // PRD §24: Knowledge Manager (popt) hanya C/R/U untuk Aturan CF,
        // bukan D. Karena itu destroy dikeluarkan dari group ini.
        Route::apiResource('aturan-cf', AturanCfController::class)
            ->except(['destroy']);
    });

// DELETE aturan CF hanya untuk Admin (PRD §24 — Aturan CF kolom Admin
// = "admin"/full-access, kolom Knowledge Manager = C/R/U tanpa D).
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function (): void {
        Route::delete('aturan-cf/{aturanCf}', [AturanCfController::class, 'destroy'])
            ->name('aturan-cf.destroy');
    });
