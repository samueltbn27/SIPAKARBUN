<?php

namespace Database\Seeders;

use App\Models\Diagnosis;
use App\Models\KasusPenanganan;
use App\Models\PermohonanPenanganan;
use App\Models\RefKelompokTani;
use App\Models\User;
use App\Services\KasusService;
use App\Services\PermohonanService;
use App\Services\StatusTransitionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * LOCAL/UAT ONLY — DO NOT USE IN PRODUCTION.
 *
 * Builds one deterministic M2 → M3 case through the existing services. The
 * marker makes reruns idempotent and keeps the simulation data identifiable.
 */
class EndToEndCaseUatSeeder extends Seeder
{
    private const MARKER = 'DATA SIMULASI UAT — FINAL PR E2E; BUKAN LAPORAN SERANGAN AKTUAL: Kabupaten Bandung (-7.025, 107.519).';

    private const DIAGNOSIS_DISEASE = 'Karat Daun Kopi';

    public function run(): void
    {
        $poktan = $this->user('poktan.uat@sipakarbun.local');
        $operator = $this->user('operator.uat@sipakarbun.local');
        $popt = $this->user('popt.uat@sipakarbun.local');

        $permohonan = PermohonanPenanganan::query()
            ->where('catatan_pemohon', 'like', self::MARKER.'%')
            ->first();

        if ($permohonan === null) {
            $diagnosis = Diagnosis::query()
                ->where('user_id', $poktan->id)
                ->where('commodity_id', 1)
                ->where('status', Diagnosis::STATUS_SELESAI)
                ->whereHas('results', fn ($query) => $query->where('disease_name_snapshot', self::DIAGNOSIS_DISEASE))
                ->latest('id')
                ->first();

            if ($diagnosis === null) {
                throw new RuntimeException('Diagnosis Knowledge UAT untuk Karat Daun Kopi tidak ditemukan.');
            }

            $kelompokTani = RefKelompokTani::query()
                ->tersedia()
                ->where('source', RefKelompokTani::SOURCE_DISBUN)
                ->where('commodity_mapping_status', 'mapped')
                ->where('commodity_ref_id', $diagnosis->commodity_id)
                ->whereNotNull('disbun_record_id')
                ->where('disbun_record_id', '5488')
                ->first()
                ?? RefKelompokTani::query()
                    ->tersedia()
                    ->where('source', RefKelompokTani::SOURCE_DISBUN)
                    ->where('commodity_mapping_status', 'mapped')
                    ->where('commodity_ref_id', $diagnosis->commodity_id)
                    ->whereNotNull('disbun_record_id')
                    ->orderBy('disbun_record_id')
                    ->first();

            if ($kelompokTani === null) {
                throw new RuntimeException('Kelompok Tani Disbun nyata dan mapped untuk komoditas diagnosis tidak ditemukan.');
            }

            /** @var PermohonanService $permohonanService */
            $permohonanService = app(PermohonanService::class);
            $permohonan = $permohonanService->buatPermohonan([
                'diagnosis_id' => $diagnosis->id,
                'kelompok_tani_id' => $kelompokTani->id,
                'latitude_kasus' => -7.025,
                'longitude_kasus' => 107.519,
                'alamat_kasus' => self::MARKER,
                'kode_kabupaten' => '3204',
                'kabupaten' => 'Kabupaten Bandung',
                'kode_kecamatan' => null,
                'kecamatan' => null,
                'kode_desa' => null,
                'kelurahan' => null,
                'catatan_pemohon' => self::MARKER.' Poktan Disbun: '.$kelompokTani->nama.' (external ID '.$kelompokTani->disbun_record_id.').',
                'evidences' => [],
            ], (int) $poktan->id);
        }

        if ($permohonan->status === PermohonanPenanganan::STATUS_DIAJUKAN) {
            app(PermohonanService::class)->review($permohonan, (int) $operator->id);
        }

        if ($permohonan->status === PermohonanPenanganan::STATUS_SEDANG_DIREVIEW) {
            app(PermohonanService::class)->terima(
                $permohonan,
                $operator,
                'DATA SIMULASI UAT — permohonan diverifikasi Operator UPTD.',
            );
        }

        $permohonan->refresh();
        if ($permohonan->status !== PermohonanPenanganan::STATUS_DITERIMA) {
            throw new RuntimeException('Permohonan UAT tidak berada pada status diterima.');
        }

        $kasus = $permohonan->kasus()->firstOrFail();
        $kasus->load('penugasanAktif');

        // This is the explicit M2 → M3 checkpoint before assignment.
        if ($kasus->current_status === KasusPenanganan::STATUS_DITERIMA
            && $kasus->penugasanAktif !== null) {
            throw new RuntimeException('Checkpoint diterima UAT gagal: kasus sudah memiliki POPT aktif.');
        }
        if ($kasus->current_status === KasusPenanganan::STATUS_DITERIMA) {
            $this->command?->info('Checkpoint sebelum assignment: request=diterima, handling=diterima, POPT=null');
        }

        /** @var KasusService $kasusService */
        $kasusService = app(KasusService::class);
        if ($kasus->current_status !== KasusPenanganan::STATUS_SELESAI) {
            $activeAssignment = $kasus->penugasanAktif;
            if ($activeAssignment === null || (int) $activeAssignment->popt_id !== (int) $popt->id) {
                $kasus = $kasusService->assignPopt(
                    $kasus,
                    $popt,
                    $operator,
                    'DATA SIMULASI UAT — POPT UAT ditugaskan.',
                );
            }

            /** @var StatusTransitionService $transitionService */
            $transitionService = app(StatusTransitionService::class);
            $next = [
                KasusPenanganan::STATUS_DITUGASKAN => [
                    KasusPenanganan::STATUS_SEDANG_DIREVIEW,
                    'Gejala dan data lokasi telah diverifikasi.',
                ],
                KasusPenanganan::STATUS_SEDANG_DIREVIEW => [
                    KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
                    'Penanganan lapangan telah dipersiapkan.',
                ],
                KasusPenanganan::STATUS_SIAP_DIEKSEKUSI => [
                    KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
                    'Pengendalian lapangan sedang dilaksanakan.',
                ],
                KasusPenanganan::STATUS_DALAM_PELAKSANAAN => [
                    KasusPenanganan::STATUS_SELESAI,
                    'Penanganan lapangan selesai dan hasil telah dicatat.',
                ],
            ];

            while ($kasus->current_status !== KasusPenanganan::STATUS_SELESAI) {
                [$target, $note] = $next[$kasus->current_status] ?? throw new RuntimeException(
                    "Status UAT tidak dapat dilanjutkan: {$kasus->current_status}."
                );
                $kasus = $transitionService->pindahkan(
                    $kasus,
                    $target,
                    $note,
                    (int) $popt->id,
                );
            }
        }

        $kasus->load(['permohonan', 'penugasanPopt.popt', 'riwayatStatus']);
        $this->command?->info("Case UAT siap: {$kasus->kasus_code}");
        $this->command?->info('Request code: '.$kasus->permohonan?->permohonan_code);
        $this->command?->info('Poktan Disbun: '.$kasus->permohonan?->kelompok_tani_name_snapshot);
        $this->command?->info('Poktan external ID: '.RefKelompokTani::query()->find($kasus->permohonan?->kelompok_tani_id)?->disbun_record_id);
        $this->command?->info('Request status: '.$kasus->permohonan?->status);
        $this->command?->info('Handling status: '.$kasus->current_status);
        $this->command?->info('Assigned POPT: '.$kasus->penugasanPopt->sortByDesc('assigned_at')->first()?->popt?->name);
        $this->command?->info('Coordinates: '.$kasus->latitude_kasus.', '.$kasus->longitude_kasus);
    }

    private function user(string $email): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            throw (new ModelNotFoundException())->setModel(User::class, [$email]);
        }

        return $user;
    }
}
