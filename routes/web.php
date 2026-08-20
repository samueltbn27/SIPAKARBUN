<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\Web\DiagnosisController as WebDiagnosisController;
use App\Http\Controllers\Web\PermohonanController as WebPermohonanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi (shared — M1 + M2 + M3)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Dashboard Umum — mengarahkan per role
|--------------------------------------------------------------------------
| Poktan → dashboard diagnosis (M2); Admin/POPT/OP → knowledge (M1).
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Modul Knowledge — Mahasiswa 1
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|popt|operator_uptd'])->prefix('knowledge')->name('knowledge.')->group(function (): void {
    Route::get('/', [KnowledgeController::class, 'dashboard'])->name('dashboard');

    // ==== READ-ONLY (admin, POPT, OP) ====
    Route::get('/komoditas', [KnowledgeController::class, 'komoditasIndex'])->name('komoditas.index');
    Route::get('/penyakit', [KnowledgeController::class, 'penyakitIndex'])->name('penyakit.index');
    Route::get('/gejala', [KnowledgeController::class, 'gejalaIndex'])->name('gejala.index');
    Route::get('/aturan-cf', [KnowledgeController::class, 'aturanCfIndex'])->name('aturan-cf.index');
    Route::get('/solusi', [KnowledgeController::class, 'solusiIndex'])->name('solusi.index');
    Route::get('/publikasi', [KnowledgeController::class, 'publikasiIndex'])->name('publikasi.index');
    Route::get('/riwayat', [KnowledgeController::class, 'riwayatIndex'])->name('riwayat.index');

    // ==== CRUD Knowledge — hanya Admin & POPT ====
    Route::middleware(['role:admin|popt'])->group(function (): void {
        Route::get('/penyakit/create', [KnowledgeController::class, 'penyakitCreate'])->name('penyakit.create');
        Route::post('/penyakit', [KnowledgeController::class, 'penyakitStore'])->name('penyakit.store');
        Route::get('/penyakit/{penyakit}/edit', [KnowledgeController::class, 'penyakitEdit'])->name('penyakit.edit');
        Route::put('/penyakit/{penyakit}', [KnowledgeController::class, 'penyakitUpdate'])->name('penyakit.update');
        Route::delete('/penyakit/{penyakit}', [KnowledgeController::class, 'penyakitDestroy'])->name('penyakit.destroy');

        Route::get('/gejala/create', [KnowledgeController::class, 'gejalaCreate'])->name('gejala.create');
        Route::post('/gejala', [KnowledgeController::class, 'gejalaStore'])->name('gejala.store');
        Route::get('/gejala/{gejala}/edit', [KnowledgeController::class, 'gejalaEdit'])->name('gejala.edit');
        Route::put('/gejala/{gejala}', [KnowledgeController::class, 'gejalaUpdate'])->name('gejala.update');
        Route::delete('/gejala/{gejala}', [KnowledgeController::class, 'gejalaDestroy'])->name('gejala.destroy');

        Route::get('/aturan-cf/create', [KnowledgeController::class, 'aturanCfCreate'])->name('aturan-cf.create');
        Route::post('/aturan-cf', [KnowledgeController::class, 'aturanCfStore'])->name('aturan-cf.store');
        Route::get('/aturan-cf/{aturanCf}/edit', [KnowledgeController::class, 'aturanCfEdit'])->name('aturan-cf.edit');
        Route::put('/aturan-cf/{aturanCf}', [KnowledgeController::class, 'aturanCfUpdate'])->name('aturan-cf.update');
        Route::delete('/aturan-cf/{aturanCf}', [KnowledgeController::class, 'aturanCfDestroy'])->name('aturan-cf.destroy');

        Route::get('/solusi/create', [KnowledgeController::class, 'solusiCreate'])->name('solusi.create');
        Route::post('/solusi', [KnowledgeController::class, 'solusiStore'])->name('solusi.store');
        Route::get('/solusi/{solusi}/edit', [KnowledgeController::class, 'solusiEdit'])->name('solusi.edit');
        Route::put('/solusi/{solusi}', [KnowledgeController::class, 'solusiUpdate'])->name('solusi.update');
        Route::delete('/solusi/{solusi}', [KnowledgeController::class, 'solusiDestroy'])->name('solusi.destroy');

        Route::post('/publikasi/toggle', [KnowledgeController::class, 'publikasiToggle'])->name('publikasi.toggle');
    });

    // ==== Halaman OP ====
    Route::middleware(['role:admin|operator_uptd'])->prefix('op')->name('op.')->group(function (): void {
        Route::get('/pengajuan-masuk', [KnowledgeController::class, 'opPengajuanMasuk'])->name('pengajuan-masuk');
        Route::get('/validasi', [KnowledgeController::class, 'opValidasiPengajuan'])->name('validasi');
        Route::get('/riwayat-pengajuan', [KnowledgeController::class, 'opRiwayatPengajuan'])->name('riwayat-pengajuan');
        Route::get('/status-kasus', [KnowledgeController::class, 'opStatusKasus'])->name('status-kasus');
    });

    // ==== Manajemen Pengguna (admin only) ====
    Route::middleware(['role:admin'])->prefix('pengguna')->name('pengguna.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::post('/{user}/approve', [\App\Http\Controllers\UserController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [\App\Http\Controllers\UserController::class, 'reject'])->name('reject');
        Route::post('/{user}/toggle', [\App\Http\Controllers\UserController::class, 'toggle'])->name('toggle');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Diagnosis — Mahasiswa 2 (Poktan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:poktan'])->prefix('diagnosis')->name('diagnosis.')->group(function (): void {
    Route::get('/', [WebDiagnosisController::class, 'create'])->name('index');
    Route::post('/', [WebDiagnosisController::class, 'store'])
        ->middleware('throttle:diagnosis');
    Route::get('/history', [WebDiagnosisController::class, 'history'])->name('history');
    Route::get('/{id}', [WebDiagnosisController::class, 'show'])
        ->whereNumber('id');
});

/*
|--------------------------------------------------------------------------
| Permohonan Penanganan — Mahasiswa 2 (Poktan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:poktan'])->prefix('permohonan')->name('permohonan.')->group(function (): void {
    Route::get('/', [WebPermohonanController::class, 'index'])->name('index');
    Route::get('/create', [WebPermohonanController::class, 'create'])->name('create');
    Route::post('/', [WebPermohonanController::class, 'store'])
        ->middleware('throttle:permohonan');
    Route::get('/{id}', [WebPermohonanController::class, 'show'])
        ->whereNumber('id');
});

/*
|--------------------------------------------------------------------------
| Operator UPTD — Mahasiswa 2 (Admin + OP)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|operator_uptd'])->prefix('operator')->name('operator.')->group(function (): void {
    Route::get('/permohonan', PlaceholderController::class)->name('permohonan');
});

/*
|--------------------------------------------------------------------------
| Kasus — Mahasiswa 2 (Admin + OP)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|operator_uptd'])->prefix('kasus')->name('kasus.')->group(function (): void {
    Route::get('/', PlaceholderController::class)->name('index');
});

/*
|--------------------------------------------------------------------------
| POPT — Mahasiswa 2 (POPT)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:popt'])->prefix('popt')->name('popt.')->group(function (): void {
    Route::get('/penugasan', PlaceholderController::class)->name('penugasan');
});

Route::get('/', function () {
    return redirect()->route('login');
});