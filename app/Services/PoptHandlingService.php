<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PerpanjanganPenugasan;
use App\Models\ProgresPenanganan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Action-based workflow for the POPT who owns an active assignment.
 *
 * The service is the only place where POPT actions combine ownership checks,
 * append-only records, deadline rules, report evidence, and completion.
 */
class PoptHandlingService
{
    public function __construct(
        private readonly StatusTransitionService $transitionService,
        private readonly PerpanjanganPenugasanService $extensionService,
        private readonly LaporanAkhirEvidenceService $evidenceService,
    ) {}

    public function acceptAssignment(int $kasusId, User $popt): KasusPenanganan
    {
        return DB::transaction(function () use ($kasusId, $popt): KasusPenanganan {
            [$kasus, $assignment] = $this->activeAssignment($kasusId, $popt, true);

            if ($kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
                throw ValidationException::withMessages([
                    'kasus_id' => 'Kasus sudah selesai dan bersifat read-only.',
                ]);
            }

            if ($assignment->accepted_at !== null) {
                throw ValidationException::withMessages([
                    'accepted_at' => 'Penugasan ini sudah diterima sebelumnya.',
                ]);
            }

            $assignment->update(['accepted_at' => now()]);

            if ($kasus->current_status === KasusPenanganan::STATUS_DITUGASKAN) {
                $kasus = $this->transitionService->pindahkan(
                    kasus: $kasus,
                    tujuan: KasusPenanganan::STATUS_SEDANG_DIREVIEW,
                    catatan: 'Penugasan diterima oleh POPT.',
                    actorId: (int) $popt->id,
                );
            }

            Log::info('POPT menerima penugasan.', [
                'kasus_id' => $kasusId,
                'penugasan_popt_id' => $assignment->id,
                'popt_id' => $popt->id,
            ]);

            return $kasus->fresh();
        });
    }

    public function addProgress(int $kasusId, User $popt, string $catatan): ProgresPenanganan
    {
        return DB::transaction(function () use ($kasusId, $popt, $catatan): ProgresPenanganan {
            [$kasus, $assignment] = $this->activeAssignment($kasusId, $popt, true);
            $this->assertAcceptedAndOpen($kasus, $assignment);

            return ProgresPenanganan::create([
                'kasus_id' => $kasus->id,
                'penugasan_popt_id' => $assignment->id,
                'actor_id' => $popt->id,
                'catatan' => $catatan,
            ]);
        });
    }

    public function requestExtension(
        int $kasusId,
        User $popt,
        string $proposedDeadlineAt,
        string $reason,
    ): PerpanjanganPenugasan {
        return DB::transaction(function () use ($kasusId, $popt, $proposedDeadlineAt, $reason): PerpanjanganPenugasan {
            [$kasus, $assignment] = $this->activeAssignment($kasusId, $popt, true);
            $this->assertAcceptedAndOpen($kasus, $assignment);

            if ($assignment->deadline_at === null) {
                throw ValidationException::withMessages([
                    'proposed_deadline_at' => 'Penugasan lama belum memiliki Target Penyelesaian sehingga perpanjangan belum dapat diajukan.',
                ]);
            }

            try {
                $proposedDeadline = Carbon::parse($proposedDeadlineAt);
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    'proposed_deadline_at' => 'Usulan target baru harus berupa tanggal dan waktu yang valid.',
                ]);
            }

            if (! $proposedDeadline->isAfter($assignment->deadline_at)) {
                throw ValidationException::withMessages([
                    'proposed_deadline_at' => 'Usulan target baru harus lebih lambat daripada Target Penyelesaian saat ini.',
                ]);
            }

            if (! $proposedDeadline->isAfter(now())) {
                throw ValidationException::withMessages([
                    'proposed_deadline_at' => 'Usulan target baru harus berada di masa depan.',
                ]);
            }

            $hasPending = $assignment->extensionRequests()
                ->where('status', PerpanjanganPenugasan::STATUS_PENDING)
                ->exists();

            if ($hasPending) {
                throw ValidationException::withMessages([
                    'extension' => 'Permintaan perpanjangan sedang menunggu keputusan Operator.',
                ]);
            }

            return PerpanjanganPenugasan::create([
                'kasus_id' => $kasus->id,
                'penugasan_popt_id' => $assignment->id,
                'requested_by' => $popt->id,
                'current_deadline_at' => $assignment->deadline_at,
                'proposed_deadline_at' => $proposedDeadline,
                'reason' => $reason,
                'status' => PerpanjanganPenugasan::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Store the final report, evidence, completion history, and assignment
     * closure as one logical database operation. New files are removed if the
     * database transaction cannot commit.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    public function completeHandling(
        int $kasusId,
        User $popt,
        string $ringkasanTindakan,
        string $hasilPenanganan,
        string $rekomendasi,
        ?string $catatanTambahan,
        array $photos,
    ): KasusPenanganan {
        if (count($photos) < LaporanAkhirEvidenceService::MIN_REQUIRED_FILES) {
            throw ValidationException::withMessages([
                'photos' => 'Minimal satu foto dokumentasi wajib diunggah.',
            ]);
        }

        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $kasusId,
                $popt,
                $ringkasanTindakan,
                $hasilPenanganan,
                $rekomendasi,
                $catatanTambahan,
                $photos,
                &$storedPaths,
            ): KasusPenanganan {
                [$kasus, $assignment] = $this->activeAssignment($kasusId, $popt, true);
                $this->assertAcceptedAndOpen($kasus, $assignment);

                if ($kasus->finalReport()->exists()) {
                    throw ValidationException::withMessages([
                        'kasus_id' => 'Laporan akhir untuk kasus ini sudah tersedia.',
                    ]);
                }

                $report = $kasus->finalReport()->create([
                    'penugasan_popt_id' => $assignment->id,
                    'submitted_by' => $popt->id,
                    'ringkasan_tindakan' => $ringkasanTindakan,
                    'hasil_penanganan' => $hasilPenanganan,
                    'rekomendasi' => $rekomendasi,
                    'catatan_tambahan' => $catatanTambahan,
                    'submitted_at' => now(),
                ]);

                foreach ($photos as $photo) {
                    if (! $photo instanceof UploadedFile) {
                        throw ValidationException::withMessages([
                            'photos' => 'Dokumentasi foto tidak valid.',
                        ]);
                    }

                    $stored = $this->evidenceService->store($photo);
                    $storedPaths[] = $stored['file_path'];
                    $report->evidences()->create([
                        ...$stored,
                        'uploaded_by' => $popt->id,
                    ]);
                }

                $this->extensionService->cancelPendingForAssignment($assignment, $popt);

                $kasus = $this->transitionService->pindahkan(
                    kasus: $kasus,
                    tujuan: KasusPenanganan::STATUS_SELESAI,
                    catatan: 'Laporan hasil penanganan dikirim oleh POPT.',
                    actorId: (int) $popt->id,
                );
                $kasus->update(['completed_at' => now()]);

                Log::info('POPT menyelesaikan penanganan dengan laporan akhir.', [
                    'kasus_id' => $kasusId,
                    'penugasan_popt_id' => $assignment->id,
                    'laporan_akhir_id' => $report->id,
                    'popt_id' => $popt->id,
                ]);

                return $kasus->fresh();
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }
    }

    /** @return array{0: KasusPenanganan, 1: PenugasanPopt} */
    private function activeAssignment(int $kasusId, User $popt, bool $lock): array
    {
        $query = KasusPenanganan::query()
            ->whereKey($kasusId)
            ->whereHas('penugasanAktif', fn ($assignment) => $assignment->where('popt_id', $popt->id))
            ->with('penugasanAktif');

        if ($lock) {
            $query->lockForUpdate();
        }

        $kasus = $query->first();
        abort_unless($kasus !== null, 403, 'Kasus ini bukan penugasan aktif Anda.');

        $assignment = $kasus->penugasanAktif;
        abort_unless($assignment !== null, 403, 'Penugasan POPT tidak aktif.');

        if ($lock) {
            $assignment = PenugasanPopt::query()->lockForUpdate()->findOrFail($assignment->id);
        }

        return [$kasus, $assignment];
    }

    private function assertAcceptedAndOpen(KasusPenanganan $kasus, PenugasanPopt $assignment): void
    {
        if ($kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'kasus_id' => 'Kasus sudah selesai dan bersifat read-only.',
            ]);
        }

        if ($assignment->accepted_at === null) {
            throw ValidationException::withMessages([
                'accepted_at' => 'Terima Penugasan terlebih dahulu sebelum melakukan aksi ini.',
            ]);
        }
    }
}
