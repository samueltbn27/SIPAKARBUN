<?php

namespace App\Http\Controllers\Web;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermohonanRequest;
use App\Models\Diagnosis;
use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\PermohonanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * PermohonanController (Web) — modul permohonan penanganan Poktan (TAHAP 4).
 *
 *   GET  /permohonan           — daftar permohonan milik user.
 *   GET  /permohonan/create    — form ajukan permohonan (dari hasil diagnosis).
 *   POST /permohonan           — simpan permohonan (status Diajukan).
 *   GET  /permohonan/{id}      — detail permohonan milik user.
 *
 * Logika bisnis diambil dari PermohonanService yang sama dengan endpoint
 * API (POST /api/permohonan) — tidak ada duplikasi aturan. Kelompok tani
 * & komoditas divalidasi terhadap Shared Integration (klien referensi).
 *
 * Lokasi kasus adalah koordinat/alamat SERANGAN OPT yang diisi pemohon,
 * TERPISAH dari lokasi kelompok tani — tidak pernah otomatis memakai
 * koordinat kelompok tani (kontrak §10).
 */
class PermohonanController extends Controller
{
    public function __construct(
        private readonly PermohonanService $service,
        private readonly KelompokTaniReferensiClient $kelompokTaniClient,
        private readonly KomoditasReferensiClient $komoditasClient,
    ) {}

    public function index(Request $request): View
    {
        Carbon::setLocale('id');

        /** @var User $user */
        $user = $request->user();

        $permohonan = $this->service->permohonanPemohon(
            userId: (int) $user->id,
            filters: $request->only(['status', 'per_page', 'created_from', 'created_to']),
        );

        [$komoditasMap, $komoditasError] = $this->muatKomoditas();

        $statusFilter = trim((string) $request->string('status', ''));
        $tanggalDari = trim((string) $request->string('created_from', ''));
        $tanggalSampai = trim((string) $request->string('created_to', ''));

        return view('permohonan.index', compact(
            'permohonan',
            'komoditasMap',
            'komoditasError',
            'statusFilter',
            'tanggalDari',
            'tanggalSampai',
        ));
    }

    public function create(Request $request): View
    {
        Carbon::setLocale('id');

        /** @var User $user */
        $user = $request->user();

        $selectedDiagnosis = null;

        $diagnoses = Diagnosis::query()
            ->untukUser($user->id)
            ->where('status', Diagnosis::STATUS_SELESAI)
            ->has('results')
            ->with(['results', 'symptoms'])
            ->latest('id')
            ->limit(50)
            ->get();

        if ($diagnosisId = $request->integer('diagnosis_id')) {
            $selectedDiagnosis = $diagnoses->firstWhere('id', $diagnosisId);
        }

        [$kelompokTaniList, $kelompokTaniError] = $this->muatKelompokTani();
        [$komoditasMap, $komoditasError] = $this->muatKomoditas();

        $komoditas = $selectedDiagnosis === null
            ? null
            : $this->komoditasClient->find((int) $selectedDiagnosis->commodity_id);

        $initialStep = old('diagnosis_id') !== null ? 2 : 1;

        return view('permohonan.create', compact(
            'diagnoses',
            'selectedDiagnosis',
            'kelompokTaniList',
            'kelompokTaniError',
            'komoditasMap',
            'komoditasError',
            'komoditas',
            'initialStep',
        ));
    }

    public function store(StorePermohonanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Sanitasi catatan pemohon: buang tag HTML (anti-XSS) lalu rapikan.
        if (isset($data['catatan_pemohon']) && is_string($data['catatan_pemohon'])) {
            $data['catatan_pemohon'] = Str::of($data['catatan_pemohon'])->stripTags()->trim()->toString();
        }

        try {
            $permohonan = $this->service->buatPermohonan($data, (int) $request->user()->id);
        } catch (ValidationException $e) {
            // Error validasi (mis. kelompok tani tidak aktif/berbeda diagnosis)
            // Biarkan Laravel menampilkan field error.
            throw $e;
        } catch (NotFoundHttpException) {
            return back()->withInput()
                ->with('error', 'Diagnosis tidak ditemukan atau bukan milik Anda.');
        } catch (Throwable $e) {
            Log::error('Web permohonan gagal tak terduga.', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat mengirim permohonan. Silakan coba lagi.');
        }

        return redirect()->route('permohonan.show', ['id' => $permohonan->id])
            ->with('success', 'Permohonan berhasil dikirim.');
    }

    public function show(Request $request, int $id): View
    {
        Carbon::setLocale('id');

        /** @var User $user */
        $user = $request->user();

        $permohonan = PermohonanPenanganan::query()
            ->whereKey($id)
            ->where('created_by', $user->id)
            ->with([
                'diagnosis.results',
                'diagnosis.symptoms',
                'evidences',
                'keputusan.operator',
                'creator',
                'reviewer',
                'kasus.penugasanAktif.popt',
                'kasus.penugasanTerakhir.popt',
                'kasus.riwayatStatus.actor',
            ])
            ->firstOrFail();

        $komoditas = $permohonan->diagnosis === null
            ? null
            : $this->komoditasClient->find((int) $permohonan->diagnosis->commodity_id);

        $kasus = $permohonan->kasus;
        // Assignment aktif diprioritaskan. Setelah kasus selesai assignment
        // ditutup, tetapi Poktan tetap harus dapat membaca POPT terakhirnya.
        $penugasan = $kasus?->penugasanAktif ?? $kasus?->penugasanTerakhir;
        $timeline = $this->bangunTimeline($permohonan);

        return view('permohonan.show', compact(
            'permohonan',
            'komoditas',
            'kasus',
            'penugasan',
            'timeline',
        ));
    }

    /**
     * Bangun timeline (TAHAP 7) yang menggabungkan siklus permohonan dan
     * kasus penanganan, diurutkan menaik dari peristiwa paling awal.
     *
     * Entri kasus berstatus `diterima` (kelahiran kasus) dilewati karena
     * sudah direpresentasikan oleh peristiwa "Permohonan Diterima" yang
     * dicatat lewat keputusan operator — menghindari duplikasi visual.
     *
     * @return array<int, array{
     *     key:string,
     *     label:string,
     *     waktu:?Carbon,
     *     catatan:?string,
     *     actor:?string,
     *     actor_role:?string,
     * }>
     */
    private function bangunTimeline(PermohonanPenanganan $permohonan): array
    {
        $penangananLabels = [
            KasusPenanganan::STATUS_DITUGASKAN => 'POPT Ditugaskan',
            KasusPenanganan::STATUS_SEDANG_DIREVIEW => 'Kasus Sedang Direview',
            KasusPenanganan::STATUS_DITUNDA => 'Ditunda',
            KasusPenanganan::STATUS_SIAP_DIEKSEKUSI => 'Siap Dieksekusi',
            KasusPenanganan::STATUS_DALAM_PELAKSANAAN => 'Dalam Pelaksanaan',
            KasusPenanganan::STATUS_SELESAI => 'Selesai',
        ];

        $entries = [];

        $entries[] = $this->entryTimeline(
            key: 'permohonan.diajukan',
            label: 'Permohonan Diajukan',
            waktu: $permohonan->created_at,
            catatan: null,
            actor: $permohonan->creator,
        );

        if ($keputusan = $permohonan->keputusan) {
            $diterima = $keputusan->keputusan === KeputusanPermohonan::KEPUTUSAN_DITERIMA;

            $entries[] = $this->entryTimeline(
                key: 'permohonan.keputusan',
                label: $diterima ? 'Permohonan Diterima' : 'Permohonan Ditolak',
                waktu: $keputusan->decided_at,
                catatan: $keputusan->catatan,
                actor: $keputusan->operator,
            );
        }

        if ($kasus = $permohonan->kasus) {
            foreach ($kasus->riwayatStatus as $riwayat) {
                if ($riwayat->status === KasusPenanganan::STATUS_DITERIMA) {
                    continue;
                }

                $entries[] = $this->entryTimeline(
                    key: 'penanganan.'.$riwayat->status,
                    label: $penangananLabels[$riwayat->status] ?? Str::headline((string) $riwayat->status),
                    waktu: $riwayat->created_at,
                    catatan: $riwayat->catatan,
                    actor: $riwayat->actor,
                );
            }
        }

        return collect($entries)
            ->sortBy(fn (array $e): int => $e['waktu']?->getTimestamp() ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     key:string,
     *     label:string,
     *     waktu:?Carbon,
     *     catatan:?string,
     *     actor:?string,
     *     actor_role:?string,
     * }
     */
    private function entryTimeline(string $key, string $label, ?Carbon $waktu, ?string $catatan, ?User $actor): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'waktu' => $waktu,
            'catatan' => $catatan,
            'actor' => $actor?->name,
            'actor_role' => $actor?->roles->first()?->name,
        ];
    }

    /**
     * Muat kelompok tani aktif dari Shared Integration.
     *
     * @return array{0: array<int, array{id:int, kode:string, nama:string, ketua:?string, is_active:bool}>, 1: bool}
     */
    private function muatKelompokTani(): array
    {
        try {
            $list = collect($this->kelompokTaniClient->all())
                ->filter(fn (array $item): bool => ($item['is_active'] ?? false) === true)
                ->sortBy('nama')
                ->take(25)
                ->values()
                ->all();

            if ($list === []) {
                return [[], true];
            }

            return [$list, false];
        } catch (Throwable $e) {
            Log::warning('Web permohonan: referensi kelompok tani gagal dimuat.', [
                'message' => $e->getMessage(),
            ]);

            return [[], true];
        }
    }

    /**
     * @return array{0: array<int, string>, 1: bool}
     */
    private function muatKomoditas(): array
    {
        try {
            $map = collect($this->komoditasClient->all())
                ->mapWithKeys(fn (array $item): array => [(int) $item['id'] => (string) $item['nama']])
                ->sort()
                ->all();

            return [$map, false];
        } catch (Throwable $e) {
            Log::warning('Web permohonan: referensi komoditas gagal dimuat.', [
                'message' => $e->getMessage(),
            ]);

            return [[], true];
        }
    }
}
