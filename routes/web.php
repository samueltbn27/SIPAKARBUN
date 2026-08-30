<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\MonitoringDashboardController;
use App\Http\Controllers\OperatorWorkflowController;
use App\Http\Controllers\PoptWorkflowController;
use App\Http\Controllers\WebGISController;
use App\Http\Controllers\Web\DiagnosisController as WebDiagnosisController;
use App\Http\Controllers\Web\PermohonanController as WebPermohonanController;
use Illuminate\Support\Facades\Route;

/* Authentication shared by M1, M2, and M3. */
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/* Dashboard umum untuk Poktan/M2. */
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

/* M3 global read-only monitoring surfaces. */
Route::middleware(['auth', 'role:admin|operator_uptd|popt|pimpinan'])->group(function (): void {
    Route::get('/webgis', [WebGISController::class, 'index'])->name('webgis.index');
});

Route::middleware(['auth', 'role:admin|operator_uptd|pimpinan'])->group(function (): void {
    Route::get('/dashboard-monitoring', [MonitoringDashboardController::class, 'index'])->name('monitoring.dashboard');
});

// Compatibility path for the existing M1 user-management screen.
Route::middleware(['auth', 'role:admin'])->prefix('pengguna')->name('pengguna.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
    Route::post('/{user}/approve', [\App\Http\Controllers\UserController::class, 'approve'])->name('approve');
    Route::post('/{user}/reject', [\App\Http\Controllers\UserController::class, 'reject'])->name('reject');
    Route::post('/{user}/toggle', [\App\Http\Controllers\UserController::class, 'toggle'])->name('toggle');
    Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
});

/* M1 Knowledge — read access for Admin/POPT/Operator, mutations Admin/Operator. */
Route::middleware(['auth', 'role:admin|popt|operator_uptd'])->prefix('knowledge')->name('knowledge.')->group(function (): void {
    Route::get('/', [KnowledgeController::class, 'dashboard'])->name('dashboard');
    Route::get('/komoditas', [KnowledgeController::class, 'komoditasIndex'])->name('komoditas.index');
    Route::get('/penyakit', [KnowledgeController::class, 'penyakitIndex'])->name('penyakit.index');
    Route::get('/gejala', [KnowledgeController::class, 'gejalaIndex'])->name('gejala.index');
    Route::get('/aturan-cf', [KnowledgeController::class, 'aturanCfIndex'])->name('aturan-cf.index');
    Route::get('/solusi', [KnowledgeController::class, 'solusiIndex'])->name('solusi.index');
    Route::get('/publikasi', [KnowledgeController::class, 'publikasiIndex'])->name('publikasi.index');
    Route::get('/riwayat', [KnowledgeController::class, 'riwayatIndex'])->name('riwayat.index');

    Route::middleware(['role:admin|operator_uptd'])->group(function (): void {
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

    Route::middleware(['role:admin|operator_uptd'])->prefix('op')->name('op.')->group(function (): void {
        Route::get('/pengajuan-masuk', fn () => redirect()->route('operator.permohonan'))->name('pengajuan-masuk');
        Route::get('/validasi', fn () => redirect()->route('operator.permohonan', ['status' => 'diajukan']))->name('validasi');
        Route::get('/riwayat-pengajuan', fn () => redirect()->route('operator.permohonan', ['status' => 'ditolak']))->name('riwayat-pengajuan');
        Route::get('/status-kasus', fn () => redirect()->route('kasus.index'))->name('status-kasus');
    });

    Route::middleware(['role:admin'])->prefix('pengguna')->name('pengguna.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::post('/{user}/approve', [\App\Http\Controllers\UserController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [\App\Http\Controllers\UserController::class, 'reject'])->name('reject');
        Route::post('/{user}/toggle', [\App\Http\Controllers\UserController::class, 'toggle'])->name('toggle');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
    });
});

/* M2 Poktan diagnosis and own requests. */
Route::middleware(['auth', 'role:poktan'])->prefix('diagnosis')->name('diagnosis.')->group(function (): void {
    Route::get('/', [WebDiagnosisController::class, 'create'])->name('index');
    Route::post('/', [WebDiagnosisController::class, 'store'])->name('store')->middleware('throttle:diagnosis');
    Route::get('/history', [WebDiagnosisController::class, 'history'])->name('history');
    Route::get('/{id}', [WebDiagnosisController::class, 'show'])->name('show')->whereNumber('id');
});

Route::middleware(['auth', 'role:poktan'])->prefix('permohonan')->name('permohonan.')->group(function (): void {
    Route::get('/', [WebPermohonanController::class, 'index'])->name('index');
    Route::get('/create', [WebPermohonanController::class, 'create'])->name('create');
    Route::post('/', [WebPermohonanController::class, 'store'])->name('store')->middleware('throttle:permohonan');
    Route::get('/{id}', [WebPermohonanController::class, 'show'])->name('show')->whereNumber('id');
});

Route::middleware(['auth', 'role:poktan'])->prefix('internal/references')->name('references.')->group(function (): void {
    Route::get('/kelompok-tani', [\App\Http\Controllers\ReferenceController::class, 'kelompokTani'])->name('kelompok-tani');
});

/* M2 web workflow surfaces backed by the existing M2 services. */
Route::middleware(['auth', 'role:admin|operator_uptd'])->prefix('operator')->name('operator.')->group(function (): void {
    Route::get('/permohonan', [OperatorWorkflowController::class, 'permohonanIndex'])->name('permohonan');
    Route::get('/permohonan/{id}', [OperatorWorkflowController::class, 'permohonanShow'])->whereNumber('id')->name('permohonan.show');
    Route::post('/permohonan/{id}/review', [OperatorWorkflowController::class, 'review'])->whereNumber('id')->name('permohonan.review');
    Route::post('/permohonan/{id}/accept', [OperatorWorkflowController::class, 'accept'])->whereNumber('id')->name('permohonan.accept');
    Route::post('/permohonan/{id}/reject', [OperatorWorkflowController::class, 'reject'])->whereNumber('id')->name('permohonan.reject');
    Route::get('/kasus', [OperatorWorkflowController::class, 'kasusIndex'])->name('kasus.index');
    Route::get('/kasus/{id}', [OperatorWorkflowController::class, 'kasusShow'])->whereNumber('id')->name('kasus.show');
    Route::post('/kasus/{id}/assign', [OperatorWorkflowController::class, 'assignPopt'])->whereNumber('id')->name('kasus.assign');
});

Route::get('/kasus', [OperatorWorkflowController::class, 'kasusIndex'])
    ->middleware(['auth', 'role:admin|operator_uptd'])
    ->name('kasus.index');

Route::middleware(['auth', 'role:popt'])->prefix('popt')->name('popt.')->group(function (): void {
    Route::get('/penugasan', [PoptWorkflowController::class, 'index'])->name('penugasan');
    Route::get('/penugasan/{id}', [PoptWorkflowController::class, 'show'])->whereNumber('id')->name('penugasan.show');
    Route::post('/penugasan/{id}/status', [PoptWorkflowController::class, 'updateStatus'])->whereNumber('id')->name('penugasan.status');
});

Route::get('/', fn () => redirect()->route('login'));
