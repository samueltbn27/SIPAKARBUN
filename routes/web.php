<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\MonitoringDashboardController;
use App\Http\Controllers\WebGISController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'role:admin|operator_uptd|popt|pimpinan'])->group(function (): void {
    Route::get('/webgis', [WebGISController::class, 'index'])->name('webgis.index');
});

Route::middleware(['auth', 'role:admin|operator_uptd|pimpinan'])->group(function (): void {
    Route::get('/dashboard-monitoring', [MonitoringDashboardController::class, 'index'])->name('monitoring.dashboard');
});

Route::middleware(['auth', 'role:admin|pakar|operator_uptd|popt'])->prefix('knowledge')->name('knowledge.')->group(function (): void {
    Route::get('/', [KnowledgeController::class, 'dashboard'])->name('dashboard');

    Route::get('/komoditas', [KnowledgeController::class, 'komoditasIndex'])->name('komoditas.index');

    Route::get('/penyakit', [KnowledgeController::class, 'penyakitIndex'])->name('penyakit.index');
    Route::get('/penyakit/create', [KnowledgeController::class, 'penyakitCreate'])->name('penyakit.create');
    Route::post('/penyakit', [KnowledgeController::class, 'penyakitStore'])->name('penyakit.store');
    Route::get('/penyakit/{penyakit}/edit', [KnowledgeController::class, 'penyakitEdit'])->name('penyakit.edit');
    Route::put('/penyakit/{penyakit}', [KnowledgeController::class, 'penyakitUpdate'])->name('penyakit.update');
    Route::delete('/penyakit/{penyakit}', [KnowledgeController::class, 'penyakitDestroy'])->name('penyakit.destroy');

    Route::get('/gejala', [KnowledgeController::class, 'gejalaIndex'])->name('gejala.index');
    Route::get('/gejala/create', [KnowledgeController::class, 'gejalaCreate'])->name('gejala.create');
    Route::post('/gejala', [KnowledgeController::class, 'gejalaStore'])->name('gejala.store');
    Route::get('/gejala/{gejala}/edit', [KnowledgeController::class, 'gejalaEdit'])->name('gejala.edit');
    Route::put('/gejala/{gejala}', [KnowledgeController::class, 'gejalaUpdate'])->name('gejala.update');
    Route::delete('/gejala/{gejala}', [KnowledgeController::class, 'gejalaDestroy'])->name('gejala.destroy');

    Route::get('/aturan-cf', [KnowledgeController::class, 'aturanCfIndex'])->name('aturan-cf.index');
    Route::get('/aturan-cf/create', [KnowledgeController::class, 'aturanCfCreate'])->name('aturan-cf.create');
    Route::post('/aturan-cf', [KnowledgeController::class, 'aturanCfStore'])->name('aturan-cf.store');
    Route::get('/aturan-cf/{aturanCf}/edit', [KnowledgeController::class, 'aturanCfEdit'])->name('aturan-cf.edit');
    Route::put('/aturan-cf/{aturanCf}', [KnowledgeController::class, 'aturanCfUpdate'])->name('aturan-cf.update');
    Route::delete('/aturan-cf/{aturanCf}', [KnowledgeController::class, 'aturanCfDestroy'])->name('aturan-cf.destroy');

    Route::get('/solusi', [KnowledgeController::class, 'solusiIndex'])->name('solusi.index');
    Route::get('/solusi/create', [KnowledgeController::class, 'solusiCreate'])->name('solusi.create');
    Route::post('/solusi', [KnowledgeController::class, 'solusiStore'])->name('solusi.store');
    Route::get('/solusi/{solusi}/edit', [KnowledgeController::class, 'solusiEdit'])->name('solusi.edit');
    Route::put('/solusi/{solusi}', [KnowledgeController::class, 'solusiUpdate'])->name('solusi.update');
    Route::delete('/solusi/{solusi}', [KnowledgeController::class, 'solusiDestroy'])->name('solusi.destroy');

    Route::get('/publikasi', [KnowledgeController::class, 'publikasiIndex'])->name('publikasi.index');
    Route::post('/publikasi/toggle', [KnowledgeController::class, 'publikasiToggle'])->name('publikasi.toggle');

    Route::get('/riwayat', [KnowledgeController::class, 'riwayatIndex'])->name('riwayat.index');

    // Manajemen Pengguna (admin only)
    Route::middleware(['role:admin'])->prefix('pengguna')->name('pengguna.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::post('/{user}/approve', [\App\Http\Controllers\UserController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [\App\Http\Controllers\UserController::class, 'reject'])->name('reject');
        Route::post('/{user}/toggle', [\App\Http\Controllers\UserController::class, 'toggle'])->name('toggle');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
    });
});

Route::get('/', function () {
    return redirect()->route('login');
});
