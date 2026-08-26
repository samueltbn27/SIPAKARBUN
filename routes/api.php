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

/* M1 → M2: active Knowledge read contract. */
Route::middleware(['auth:sanctum'])
    ->group(function (): void {
        Route::get('/penyakit', [KnowledgeApiController::class, 'penyakit']);
        Route::get('/gejala', [KnowledgeApiController::class, 'gejala']);
    });

/* M2: diagnosis history and execution. */
Route::middleware(['auth:sanctum'])
    ->prefix('diagnosis')
    ->group(function (): void {
        Route::post('/', [DiagnosisController::class, 'store'])
            ->middleware('throttle:diagnosis');
        Route::get('/', [DiagnosisController::class, 'index']);
        Route::get('/{id}', [DiagnosisController::class, 'show'])
            ->whereNumber('id');
    });

/* M2: Poktan-owned handling requests. */
Route::middleware(['auth:sanctum'])
    ->prefix('permohonan')
    ->group(function (): void {
        Route::post('/', [PermohonanController::class, 'store'])
            ->middleware('throttle:permohonan');
        Route::get('/', [PermohonanController::class, 'index']);
        Route::get('/{id}', [PermohonanController::class, 'show'])
            ->whereNumber('id');
    });

/* M2: Operator review and decision workflow. */
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
| Kasus Penanganan — shared M2 → M3 read contract
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin|operator_uptd|popt|pimpinan'])
    ->prefix('kasus')
    ->group(function (): void {
        Route::get('/', [KasusController::class, 'index']);
        Route::get('/{id}', [KasusController::class, 'show'])
            ->whereNumber('id');
        Route::get('/{id}/history', [KasusController::class, 'history'])
            ->whereNumber('id');
    });

/* M2 mutations remain restricted to Admin/Operator UPTD. */
Route::middleware(['auth:sanctum', 'role:admin|operator_uptd'])
    ->prefix('kasus')
    ->group(function (): void {
        Route::post('/{id}/assign-popt', [KasusController::class, 'assignPopt'])
            ->whereNumber('id');
    });

/* M2: POPT assigned-case read and status update. */
Route::middleware(['auth:sanctum', 'role:popt'])
    ->prefix('popt')
    ->group(function (): void {
        Route::get('/penugasan', [PoptController::class, 'index']);
        Route::get('/kasus/{id}', [PoptController::class, 'show'])
            ->whereNumber('id');
        Route::post('/kasus/{id}/status', [PoptController::class, 'updateStatus'])
            ->whereNumber('id');
    });

/* M1: Knowledge CRUD is owned by Admin/POPT; OP remains read-only. */
Route::middleware(['auth:sanctum', 'role:admin|popt'])
    ->prefix('admin')
    ->group(function (): void {
        Route::apiResource('penyakit', PenyakitController::class);
        Route::apiResource('gejala', GejalaController::class);
        Route::apiResource('solusi', SolusiController::class);
        Route::apiResource('aturan-cf', AturanCfController::class)
            ->except(['destroy']);
    });

Route::middleware(['auth:sanctum', 'role:admin|popt'])
    ->prefix('admin')
    ->group(function (): void {
        Route::delete('aturan-cf/{aturanCf}', [AturanCfController::class, 'destroy'])
            ->name('aturan-cf.destroy');
    });
