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
| CRUD internal Admin/POPT
|--------------------------------------------------------------------------
| Wajib login (auth:sanctum) + role admin atau popt. OP (operator)
| tidak memiliki akses CRUD — hanya read via endpoint kontrak M2.
*/
Route::middleware(['auth:sanctum', 'role:admin|popt'])
    ->prefix('admin')
    ->group(function (): void {
        Route::apiResource('penyakit', PenyakitController::class);
        Route::apiResource('gejala', GejalaController::class);
        Route::apiResource('solusi', SolusiController::class);
        // Aturan CF: C/R/U untuk Admin & POPT.
        Route::apiResource('aturan-cf', AturanCfController::class)
            ->except(['destroy']);
    });

// DELETE aturan CF: Admin & POPT (POPT pemegang CRUD knowledge).
Route::middleware(['auth:sanctum', 'role:admin|popt'])
    ->prefix('admin')
    ->group(function (): void {
        Route::delete('aturan-cf/{aturanCf}', [AturanCfController::class, 'destroy'])
            ->name('aturan-cf.destroy');
    });
