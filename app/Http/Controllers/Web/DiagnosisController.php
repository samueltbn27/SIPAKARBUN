<?php

namespace App\Http\Controllers\Web;

use App\Contracts\KomoditasReferensiClient;
use App\Exceptions\KnowledgeApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiagnosisRequest;
use App\Models\Diagnosis;
use App\Models\User;
use App\Services\DiagnosisService;
use App\Services\KnowledgeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * DiagnosisController (Web) — alur diagnosis untuk Poktan (TAHAP 2).
 *
 *   GET  /diagnosis            — wizard: pilih komoditas → gejala → keyakinan.
 *   POST /diagnosis            — proses & simpan diagnosis.
 *   GET  /diagnosis/history    — riwayat diagnosis milik user.
 *   GET  /diagnosis/{id}       — detail hasil diagnosis milik user.
 *
 * SEMUA data knowledge (komoditas, gejala, penyakit + rule CF + solusi)
 * diambil dari sumber yang sama dengan endpoint kontrak:
 *   - komoditas : KomoditasReferensiClient (GET /api/referensi/komoditas)
 *   - gejala    : KnowledgeService        (GET /api/gejala)
 *   - penyakit  : KnowledgeService        (GET /api/penyakit)
 * Tidak ada data yang di-hardcode dan tidak ada tabel knowledge baru.
 */
class DiagnosisController extends Controller
{
    public function __construct(
        private readonly DiagnosisService $diagnosisService,
        private readonly KnowledgeService $knowledge,
        private readonly KomoditasReferensiClient $komoditasClient,
    ) {}

    public function create(): View
    {
        [$komoditas, $gejala, $komoditasGejalaMap, $knowledgeError] = $this->muatDataKnowledge();

        $initialStep = 1;
        if (old('commodity_id') !== null) {
            $initialStep = 2;
        }
        if (old('symptom_ids') !== null) {
            $initialStep = 3;
        }
        if (old('symptom_confidence') !== null) {
            $initialStep = 4;
        }

        return view('diagnosis.create', compact('komoditas', 'gejala', 'komoditasGejalaMap', 'knowledgeError', 'initialStep'));
    }

    public function store(StoreDiagnosisRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $results = $this->diagnosisService->diagnose(
                commodityId: (int) $request->validated('commodity_id'),
                symptomIds: $request->validated('symptom_ids'),
                userId: $user?->id,
                cfUser: $request->validated('symptom_confidence', []),
            );
        } catch (KnowledgeApiException $e) {
            Log::warning('Web diagnosis gagal: knowledge tidak tersedia.', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Data knowledge tidak dapat dimuat. Silakan coba kembali.');
        } catch (Throwable $e) {
            Log::error('Web diagnosis gagal tak terduga.', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menjalankan diagnosis. Silakan coba lagi.');
        }

        $first = $results->first();

        if ($first === null) {
            return back()->withInput()->with('error', 'Tidak ada penyakit yang cocok dengan gejala yang dipilih.');
        }

        return redirect()->route('diagnosis.show', ['id' => (int) $first['diagnosis_id']])
            ->with('success', 'Diagnosis berhasil dijalankan.');
    }

    public function history(Request $request): View
    {
        Carbon::setLocale('id');

        /** @var User $user */
        $user = $request->user();

        $search = trim((string) $request->string('q', ''));
        $komoditasFilter = $request->integer('komoditas');
        $tanggal = trim((string) $request->string('tanggal', ''));
        $sort = (string) $request->string('sort', 'terbaru');

        $query = Diagnosis::query()
            ->untukUser($user->id)
            ->with(['symptoms', 'results']);

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('kode', 'like', "%{$search}%")
                    ->orWhereHas('results', fn (Builder $r): Builder => $r->where('disease_name_snapshot', 'like', "%{$search}%"));
            });
        }

        if ($komoditasFilter > 0) {
            $query->where('commodity_id', $komoditasFilter);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) === 1) {
            $query->whereDate('created_at', $tanggal);
        }

        $allowedSorts = ['terbaru', 'terlama', 'kode', 'komoditas', 'cf', 'penyakit'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'terbaru';
        }

        switch ($sort) {
            case 'terlama':
                $query->oldest('id');
                break;
            case 'kode':
                $query->orderBy('kode')->orderByDesc('id');
                break;
            case 'komoditas':
                $query->orderBy('commodity_id')->orderByDesc('id');
                break;
            case 'cf':
            case 'penyakit':
                // Urutkan berdasar hasil peringkat 1 (penyakit utama).
                $query->leftJoin('diagnosis_results as dr_sort', function (JoinClause $join): void {
                    $join->on('diagnoses.id', '=', 'dr_sort.diagnosis_id')
                        ->where('dr_sort.ranking', '=', 1);
                })->select('diagnoses.*');

                if ($sort === 'cf') {
                    $query->orderByDesc('dr_sort.cf_value')->orderByDesc('diagnoses.id');
                } else {
                    $query->orderBy('dr_sort.disease_name_snapshot')->orderByDesc('diagnoses.id');
                }
                break;
            default:
                $query->latest('id');
        }

        $diagnoses = $query
            ->paginate(max(1, min($request->integer('per_page', 15), 100)))
            ->withQueryString();

        [$komoditasMap, $komoditasError] = $this->muatKomoditas();

        return view('diagnosis.history', compact(
            'diagnoses',
            'komoditasMap',
            'komoditasError',
            'search',
            'komoditasFilter',
            'tanggal',
            'sort',
        ));
    }

    public function show(Request $request, int $id): View
    {
        Carbon::setLocale('id');

        /** @var User $user */
        $user = $request->user();

        $diagnosis = Diagnosis::query()
            ->untukUser($user->id)
            ->with(['symptoms', 'results'])
            ->findOrFail($id);

        [$komoditas, $komoditasError] = $this->muatKomoditasUntukShow((int) $diagnosis->commodity_id);

        return view('diagnosis.show', compact('diagnosis', 'komoditas', 'komoditasError'));
    }

    /**
     * Cari nama komoditas untuk halaman hasil. Kalau referensi gagal,
     * halaman tetap tampil (state error): nama ditampilkan sebagai "#id".
     *
     * @return array{0: ?array, 1: bool}
     */
    private function muatKomoditasUntukShow(int $commodityId): array
    {
        try {
            return [$this->komoditasClient->find($commodityId), false];
        } catch (Throwable $e) {
            Log::warning('Web diagnosis: referensi komoditas gagal dimuat (detail hasil).', [
                'commodity_id' => $commodityId,
                'message' => $e->getMessage(),
            ]);

            return [null, true];
        }
    }

    /**
     * Muat komoditas + knowledge untuk wizard. Kalau knowledge API gagal,
     * halaman tetap tampil dengan pesan error (state error yang diminta).
     *
     * @return array{0: array, 1: array, 2: array, 3: ?string}
     */
    private function muatDataKnowledge(): array
    {
        $komoditas = [];
        $gejala = [];
        $map = [];
        $error = null;

        try {
            $komoditas = $this->komoditasClient->all();
            $penyakit = $this->knowledge->penyakit();
            $gejala = $this->knowledge->gejala()->all();

            foreach ($penyakit as $penyakitItem) {
                foreach ($penyakitItem['komoditas_id'] ?? [] as $komoditasId) {
                    foreach ($penyakitItem['aturan_cf'] ?? [] as $rule) {
                        $map[$komoditasId][(int) $rule['gejala_id']] = true;
                    }
                }
            }

            $map = collect($map)->map(fn (array $ids): array => array_keys($ids))->all();
        } catch (KnowledgeApiException|Throwable $e) {
            Log::warning('Web diagnosis: data knowledge gagal dimuat.', [
                'message' => $e->getMessage(),
            ]);

            $error = 'Data knowledge tidak dapat dimuat. Silakan coba kembali.';
        }

        return [$komoditas, $gejala, $map, $error];
    }

    private function muatKomoditas(): array
    {
        try {
            $map = collect($this->komoditasClient->all())
                ->mapWithKeys(fn (array $item): array => [(int) $item['id'] => (string) $item['nama']])
                ->sort()
                ->all();

            return [$map, false];
        } catch (Throwable $e) {
            Log::warning('Web diagnosis: referensi komoditas gagal dimuat.', [
                'message' => $e->getMessage(),
            ]);

            return [[], true];
        }
    }
}
