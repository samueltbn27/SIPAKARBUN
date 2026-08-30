<?php

namespace App\Services;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * PermohonanService — business logic permohonan penanganan (kontrak §9-12).
 *
 * Alur:
 *   Poktan membuat permohonan berbasis diagnosis MILIK-SENDIRI yang sudah
 *   selesai. Operator UPTD mereview (status → sedang_direview), lalu
 *   memutuskan diterima (lahirlah KasusPenanganan) atau ditolak (alasan
 *   wajib). Semua perubahan status & keputusan tercatat append-only di
 *   tabel keputusan_permohonan / kasus_penanganan.
 *
 * Integritas data:
 *   - Kelompok tani DIVALIDASI ke KolompokTaniReferensiClient (Shared
 *     Integration). Jika klien gagal/tidak aktif → permohonan ditolak
 *     (bukan fallback diam-diam), karena kepemilikan kewilayahan harus
 *     merujuk sumber kebenaran.
 *   - Koordinat & alamat kasus disimpan apa adanya dari pemohon (validasi
 *     rentang di Request), bukan turunan kelompok tani (kontrak §10).
 *   - Setiap keputusan OTORITATIF lewat transaksi DB.
 */
class PermohonanService
{
    public function __construct(
        private readonly KelompokTaniReferensiClient $kelompokTaniClient,
        private readonly KomoditasReferensiClient $komoditasClient,
        private readonly EvidenceFileHandler $evidenceFileHandler,
        private readonly StatusTransitionService $statusTransition,
    ) {}

    /**
     * Buat permohonan penanganan baru untuk user (Poktan/staf).
     *
     * @param  array{
     *     diagnosis_id:int,
     *     kelompok_tani_id:int,
     *     latitude_kasus:?float,
     *     longitude_kasus:?float,
     *     alamat_kasus:?string,
     *     kode_kabupaten:?string,
     *     kabupaten:?string,
     *     kode_kecamatan:?string,
     *     kecamatan:?string,
     *     kode_desa:?string,
     *     kelurahan:?string,
     *     catatan_pemohon:?string,
     *     evidences:array<int, UploadedFile>,
     * }  $data
     */
    public function buatPermohonan(array $data, int $userId): PermohonanPenanganan
    {
        // Diagnosis harus milik user yang login, agar permohonan tidak
        // bisa dibangun dari transaksi orang lain.
        $diagnosis = Diagnosis::query()
            ->whereKey($data['diagnosis_id'])
            ->where('user_id', $userId)
            ->where('status', Diagnosis::STATUS_SELESAI)
            ->has('results')
            ->first();

        abort_unless($diagnosis !== null, 404, 'Diagnosis tidak ditemukan atau bukan milik Anda.');

        $kelompokTani = $this->kelompokTaniClient->find((int) $data['kelompok_tani_id']);

        if ($kelompokTani === null || ! (($kelompokTani['is_active'] ?? false) === true)) {
            throw ValidationException::withMessages([
                'kelompok_tani_id' => 'Kelompok tani tidak ditemukan atau tidak aktif.',
            ]);
        }

        return DB::transaction(function () use ($data, $userId, $diagnosis, $kelompokTani): PermohonanPenanganan {
            $permohonan = PermohonanPenanganan::create([
                'permohonan_code' => $this->generatePermohonanCode(),
                'diagnosis_id' => $diagnosis->id,
                'kelompok_tani_id' => $kelompokTani['id'],
                'kelompok_tani_name_snapshot' => $kelompokTani['nama'],
                'latitude_kasus' => $data['latitude_kasus'] ?? null,
                'longitude_kasus' => $data['longitude_kasus'] ?? null,
                'alamat_kasus' => $data['alamat_kasus'] ?? null,
                'kode_kabupaten' => $data['kode_kabupaten'] ?? null,
                'kabupaten' => $data['kabupaten'] ?? null,
                'kode_kecamatan' => $data['kode_kecamatan'] ?? null,
                'kecamatan' => $data['kecamatan'] ?? null,
                'kode_desa' => $data['kode_desa'] ?? null,
                'kelurahan' => $data['kelurahan'] ?? null,
                'catatan_pemohon' => $data['catatan_pemohon'] ?? null,
                'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
                'created_by' => $userId,
            ]);

            foreach ($data['evidences'] ?? [] as $file) {
                $stored = $this->evidenceFileHandler->store($file);
                $permohonan->evidences()->create($stored);
            }

            Log::info('Permohonan penanganan diajukan.', [
                'permohonan_id' => $permohonan->id,
                'user_id' => $userId,
                'diagnosis_id' => $diagnosis->id,
                'kelompok_tani_id' => $kelompokTani['id'],
            ]);

            return $permohonan;
        });
    }

    /**
     * Operator mulai mereview permohonan (status → sedang_direview) dan
     * mencatat siapa yang mereview. Tanpa transaksi: cukup satu update.
     */
    public function review(PermohonanPenanganan $permohonan, int $operatorId): PermohonanPenanganan
    {
        $this->pastikanBelumDiputuskan($permohonan);

        $permohonan->update([
            'status' => PermohonanPenanganan::STATUS_SEDANG_DIREVIEW,
            'reviewed_by' => $operatorId,
            'reviewed_at' => now(),
        ]);

        Log::info('Permohonan mulai direview.', [
            'permohonan_id' => $permohonan->id,
            'operator_id' => $operatorId,
        ]);

        return $permohonan->fresh();
    }

    /**
     * Terima permohonan: catat keputusan DITERIMA lalu lahirkan
     * KasusPenanganan (satu transaksi).
     */
    public function terima(PermohonanPenanganan $permohonan, User $operator, ?string $catatan): KasusPenanganan
    {
        return DB::transaction(function () use ($permohonan, $operator, $catatan): KasusPenanganan {
            $this->pastikanBelumDiputuskan($permohonan);

            $permohonan->update([
                'status' => PermohonanPenanganan::STATUS_DITERIMA,
                'reviewed_by' => $operator->getKey(),
                'reviewed_at' => $permohonan->reviewed_at ?? now(),
            ]);

            KeputusanPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITERIMA,
                'catatan' => $catatan,
                'operator_id' => $operator->getKey(),
                'decided_at' => now(),
            ]);

            $kasus = $this->buatKasusDariPermohonan($permohonan, $operator);
            $this->statusTransition->catatStatusAwal($kasus, (int) $operator->getKey());

            Log::info('Permohonan diterima, kasus dibuat.', [
                'permohonan_id' => $permohonan->id,
                'kasus_id' => $kasus->id,
                'operator_id' => $operator->getKey(),
            ]);

            return $kasus;
        });
    }

    /**
     * Tolak permohonan: catat keputusan DITOLAK (catatan wajib).
     */
    public function tolak(PermohonanPenanganan $permohonan, User $operator, string $catatan): PermohonanPenanganan
    {
        return DB::transaction(function () use ($permohonan, $operator, $catatan): PermohonanPenanganan {
            $this->pastikanBelumDiputuskan($permohonan);

            $permohonan->update([
                'status' => PermohonanPenanganan::STATUS_DITOLAK,
                'reviewed_by' => $operator->getKey(),
                'reviewed_at' => $permohonan->reviewed_at ?? now(),
            ]);

            KeputusanPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITOLAK,
                'catatan' => $catatan,
                'operator_id' => $operator->getKey(),
                'decided_at' => now(),
            ]);

            Log::info('Permohonan ditolak.', [
                'permohonan_id' => $permohonan->id,
                'operator_id' => $operator->getKey(),
            ]);

            return $permohonan->fresh();
        });
    }

    /**
     * Daftar permohonan milik satu user (endpoint "permohonan saya").
     */
    public function permohonanPemohon(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = PermohonanPenanganan::query()
            ->with(['diagnosis.results', 'diagnosis.symptoms', 'keputusan', 'kasus', 'evidences'])
            ->where('created_by', $userId);

        return $this->filterQuery($query, $filters)->latest('id')->paginate($this->perPage($filters));
    }

    /**
     * Daftar permohonan untuk Operator UPTD (semua, bisa disaring status).
     */
    public function permohonanOperator(array $filters = []): LengthAwarePaginator
    {
        $query = PermohonanPenanganan::query()
            ->with(['diagnosis.results', 'diagnosis.symptoms', 'keputusan', 'kasus', 'evidences', 'creator']);

        return $this->filterQuery($query, $filters)->latest('id')->paginate($this->perPage($filters));
    }

    /**
     * Detail permohonan untuk operator.
     */
    public function detailPermohonan(int $id): PermohonanPenanganan
    {
        return PermohonanPenanganan::query()
            ->with(['diagnosis.results', 'diagnosis.symptoms', 'keputusan.operator', 'kasus.permohonan', 'evidences', 'creator', 'reviewer'])
            ->findOrFail($id);
    }

    private function filterQuery($query, array $filters)
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query;
    }

    private function perPage(array $filters): int
    {
        return max(1, min((int) ($filters['per_page'] ?? 15), 100));
    }

    private function pastikanBelumDiputuskan(PermohonanPenanganan $permohonan): void
    {
        $sudahDiputus = in_array($permohonan->status, [
            PermohonanPenanganan::STATUS_DITERIMA,
            PermohonanPenanganan::STATUS_DITOLAK,
        ], true);

        if ($sudahDiputus) {
            throw ValidationException::withMessages([
                'permohonan_id' => 'Permohonan sudah diputuskan ('.$permohonan->status.').',
            ]);
        }
    }

    private function buatKasusDariPermohonan(PermohonanPenanganan $permohonan, User $operator): KasusPenanganan
    {
        $diagnosis = $permohonan->diagnosis;
        $komoditas = $this->komoditasClient->find((int) $diagnosis?->commodity_id);

        // Penyakit utama = hasil peringkat pertama dari diagnosis (CF tertinggi).
        /** @var Collection $results */
        $results = $diagnosis->results;
        $penyakitUtama = $results->first();

        return KasusPenanganan::create([
            'permohonan_id' => $permohonan->id,
            'kasus_code' => $this->generateKasusCode(),
            'current_status' => KasusPenanganan::STATUS_DITERIMA,
            'komoditas_id' => $diagnosis->commodity_id,
            'komoditas_code_snapshot' => $komoditas['kode'] ?? null,
            'komoditas_name_snapshot' => $komoditas['nama'] ?? null,
            'penyakit_id' => $penyakitUtama?->disease_id,
            'penyakit_kode_snapshot' => null,
            'penyakit_name_snapshot' => $penyakitUtama?->disease_name_snapshot,
            'latitude_kasus' => $permohonan->latitude_kasus,
            'longitude_kasus' => $permohonan->longitude_kasus,
            'created_by' => $operator->getKey(),
        ]);
    }

    private function generatePermohonanCode(): string
    {
        $urutanHariIni = PermohonanPenanganan::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'PM-'.now()->format('Ymd').'-'.Str::padLeft((string) $urutanHariIni, 4, '0');
    }

    private function generateKasusCode(): string
    {
        $urutanHariIni = KasusPenanganan::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'KS-'.now()->format('Ymd').'-'.Str::padLeft((string) $urutanHariIni, 4, '0');
    }
}
