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
| CRUD internal Admin/Operator/POPT
|--------------------------------------------------------------------------
| Wajib login (auth:sanctum) + role admin, operator_uptd, atau popt.
*/
Route::middleware(['auth:sanctum', 'role:admin|operator_uptd|popt'])
    ->prefix('admin')
    ->group(function (): void {
        Route::apiResource('penyakit', PenyakitController::class);
        Route::apiResource('gejala', GejalaController::class);
        Route::apiResource('solusi', SolusiController::class);
        // Aturan CF: C/R/U untuk Operator/POPT, tanpa D.
        // Destroy dikeluarkan dari group ini.
        Route::apiResource('aturan-cf', AturanCfController::class)
            ->except(['destroy']);
    });

// DELETE aturan CF hanya untuk Admin (full-access).
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function (): void {
        Route::delete('aturan-cf/{aturanCf}', [AturanCfController::class, 'destroy'])
            ->name('aturan-cf.destroy');
    });
