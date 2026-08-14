<?php

use App\Http\Controllers\AturanCfController;
use App\Http\Controllers\GejalaController;
use App\Http\Controllers\KnowledgeApiController;
use App\Http\Controllers\PenyakitController;
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
| CRUD internal Admin/Pakar
|--------------------------------------------------------------------------
| Wajib login (auth:sanctum) + role admin atau pakar.
*/
Route::middleware(['auth:sanctum', 'role:admin|pakar'])
    ->prefix('admin')
    ->group(function (): void {
        Route::apiResource('penyakit', PenyakitController::class);
        Route::apiResource('gejala', GejalaController::class);
        Route::apiResource('solusi', SolusiController::class);
        // PRD §24: Knowledge Manager (pakar) hanya C/R/U untuk Aturan CF,
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
