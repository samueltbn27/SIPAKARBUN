<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\Web\DiagnosisController as WebDiagnosisController;
use App\Http\Controllers\Web\PermohonanController as WebPermohonanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web — Mahasiswa 2 (TAHAP 1: kerangka, navigasi, dan guard role)
|--------------------------------------------------------------------------
| Autentikasi web memakai guard session; navigasi disajikan per role.
| Route di bawah sudah di-guard role — membuka URL role lain langsung
| akan mendapat 403 (halaman unauthorized).
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard shell — semua role terautentikasi.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==== Poktan — Modul Diagnosis (TAHAP 2) ====
    Route::middleware('role:poktan')->prefix('diagnosis')->name('diagnosis.')->group(function (): void {
        Route::get('/', [WebDiagnosisController::class, 'create'])->name('index');
        Route::post('/', [WebDiagnosisController::class, 'store'])
            ->middleware('throttle:diagnosis')
            ->name('store');
        Route::get('/history', [WebDiagnosisController::class, 'history'])->name('history');
        Route::get('/{id}', [WebDiagnosisController::class, 'show'])
            ->whereNumber('id')
            ->name('show');
    });

    // ==== Poktan — Modul Permohonan Penanganan (TAHAP 4) ====
    Route::middleware('role:poktan')->prefix('permohonan')->name('permohonan.')->group(function (): void {
        Route::get('/', [WebPermohonanController::class, 'index'])->name('index');
        Route::get('/create', [WebPermohonanController::class, 'create'])->name('create');
        Route::post('/', [WebPermohonanController::class, 'store'])
            ->middleware('throttle:permohonan')
            ->name('store');
        Route::get('/{id}', [WebPermohonanController::class, 'show'])
            ->whereNumber('id')
            ->name('show');
    });

    // ==== Operator UPTD / Admin ====
    Route::middleware('role:admin|operator_uptd')->prefix('operator')->name('operator.')->group(function (): void {
        Route::get('/permohonan', PlaceholderController::class)->name('permohonan');
    });

    Route::middleware('role:admin|operator_uptd')->prefix('kasus')->name('kasus.')->group(function (): void {
        Route::get('/', PlaceholderController::class)->name('index');
    });

    // ==== POPT ====
    Route::middleware('role:popt')->prefix('popt')->name('popt.')->group(function (): void {
        Route::get('/penugasan', PlaceholderController::class)->name('penugasan');
    });

    // ==== Admin only ====
    Route::middleware('role:admin')->prefix('pengguna')->name('pengguna.')->group(function (): void {
        Route::get('/', PlaceholderController::class)->name('index');
    });
});
