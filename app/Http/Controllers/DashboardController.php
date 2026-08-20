<?php

namespace App\Http\Controllers;

use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Dashboard shell — halaman awal setelah login.
 *
 * TAHAP 1: kerangka (shell) + navigasi per role. Statistik memakai data
 * nyata yang sudah ada. Untuk role poktan, dashboard dilengkapi ringkasan
 * kasus (aktif/selesai) dan "Aktivitas Terakhir" (diagnosis & permohonan
 * terbaru). Statistik adalah perhitungan dari relasi yang benar-benar
 * disimpan backend — TIDAK ada endpoint statistik palsu.
 *
 * Dashboard tetap dipakai bersama semua role (rute `auth` existing);
 * konten Poktan hanya dirender ketika role = poktan.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly KomoditasReferensiClient $komoditasClient,
    ) {}

    public function index(Request $request): View
    {
        Carbon::setLocale('id');

        /** @var User $user */
        $user = $request->user();
        $role = $user->roles->pluck('name')->first() ?? 'user';

        $stats = match ($role) {
            'poktan' => [
                ['label' => 'Diagnosis Saya', 'value' => Diagnosis::untukUser($user->id)->count(), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'tone' => 'emerald'],
                ['label' => 'Permohonan Saya', 'value' => PermohonanPenanganan::where('created_by', $user->id)->count(), 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'tone' => 'sky'],
                ['label' => 'Kasus Aktif', 'value' => PermohonanPenanganan::where('created_by', $user->id)->whereHas('kasus', fn ($q) => $q->where('current_status', '!=', KasusPenanganan::STATUS_SELESAI))->count(), 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'tone' => 'amber'],
                ['label' => 'Kasus Selesai', 'value' => PermohonanPenanganan::where('created_by', $user->id)->whereHas('kasus', fn ($q) => $q->where('current_status', KasusPenanganan::STATUS_SELESAI))->count(), 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'tone' => 'emerald'],
            ],
            'operator_uptd', 'admin' => [
                ['label' => 'Permohonan Masuk', 'value' => PermohonanPenanganan::count(), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'tone' => 'emerald'],
                ['label' => 'Kasus Berjalan', 'value' => KasusPenanganan::where('current_status', '!=', KasusPenanganan::STATUS_SELESAI)->count(), 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z', 'tone' => 'sky'],
            ],
            'popt' => [
                ['label' => 'Penugasan Aktif', 'value' => PenugasanPopt::where('popt_id', $user->id)->where('status', PenugasanPopt::STATUS_AKTIF)->count(), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'tone' => 'emerald'],
                ['label' => 'Kasus Dikelola', 'value' => KasusPenanganan::whereHas('penugasanAktif', fn ($q) => $q->where('popt_id', $user->id))->count(), 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'tone' => 'sky'],
            ],
            default => [],
        };

        $recents = $role === 'poktan' ? $this->muatAktivitasPoktan((int) $user->id) : [];

        return view('dashboard.index', compact('user', 'role', 'stats', 'recents'));
    }

    /**
     * Aktivitas terakhir untuk role poktan: diagnosis & permohonan terbaru
     * + peta nama komoditas (fallback "#id" bila referensi gagal dimuat).
     *
     * @return array{recent_diagnoses: array, recent_permohonan: array, komoditas_map: array<int, string>, komoditas_error: bool}
     */
    private function muatAktivitasPoktan(int $userId): array
    {
        $recentDiagnoses = Diagnosis::query()
            ->untukUser($userId)
            ->with(['results', 'symptoms'])
            ->latest('id')
            ->limit(3)
            ->get();

        $recentPermohonan = PermohonanPenanganan::query()
            ->where('created_by', $userId)
            ->with(['diagnosis.results', 'kasus'])
            ->latest('id')
            ->limit(3)
            ->get();

        [$komoditasMap, $komoditasError] = $this->muatKomoditas();

        return [
            'recent_diagnoses' => $recentDiagnoses,
            'recent_permohonan' => $recentPermohonan,
            'komoditas_map' => $komoditasMap,
            'komoditas_error' => $komoditasError,
        ];
    }

    /** @return array{0: array<int, string>, 1: bool} */
    private function muatKomoditas(): array
    {
        try {
            $map = collect($this->komoditasClient->all())
                ->mapWithKeys(fn (array $item): array => [(int) $item['id'] => (string) $item['nama']])
                ->sort()
                ->all();

            return [$map, false];
        } catch (Throwable $e) {
            Log::warning('Dashboard: referensi komoditas gagal dimuat.', [
                'message' => $e->getMessage(),
            ]);

            return [[], true];
        }
    }
}
