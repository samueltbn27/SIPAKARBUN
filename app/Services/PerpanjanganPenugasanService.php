<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PerpanjanganPenugasan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Application service for the Operator review of POPT deadline extensions.
 * Requests remain historical records; only an approved request updates the
 * active assignment deadline.
 */
class PerpanjanganPenugasanService
{
    public function approve(PerpanjanganPenugasan $extension, User $reviewer, ?string $reviewNote = null): PerpanjanganPenugasan
    {
        return DB::transaction(function () use ($extension, $reviewer, $reviewNote): PerpanjanganPenugasan {
            $extension = $this->lockExtension($extension);
            $assignment = $extension->penugasanPopt()->lockForUpdate()->first();
            $kasus = $extension->kasus()->lockForUpdate()->first();

            $this->assertReviewable($extension, $assignment, $kasus);

            $proposedDeadline = $extension->proposed_deadline_at;
            if ($proposedDeadline === null || ! $proposedDeadline->isAfter(now())) {
                throw ValidationException::withMessages([
                    'extension_id' => 'Usulan deadline harus masih berada di masa depan.',
                ]);
            }

            if ($assignment->deadline_at !== null && ! $proposedDeadline->isAfter($assignment->deadline_at)) {
                throw ValidationException::withMessages([
                    'extension_id' => 'Usulan deadline harus lebih lambat daripada deadline saat ini.',
                ]);
            }

            $assignment->update(['deadline_at' => $proposedDeadline]);
            $extension->update([
                'status' => PerpanjanganPenugasan::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);

            Log::info('Permintaan perpanjangan penugasan disetujui.', [
                'extension_id' => $extension->id,
                'kasus_id' => $extension->kasus_id,
                'reviewed_by' => $reviewer->id,
                'new_deadline_at' => $proposedDeadline->toIso8601String(),
            ]);

            return $extension->fresh(['penugasanPopt', 'kasus', 'reviewer']);
        });
    }

    public function reject(PerpanjanganPenugasan $extension, User $reviewer, string $reviewNote): PerpanjanganPenugasan
    {
        if (trim($reviewNote) === '') {
            throw ValidationException::withMessages([
                'review_note' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($extension, $reviewer, $reviewNote): PerpanjanganPenugasan {
            $extension = $this->lockExtension($extension);
            $assignment = $extension->penugasanPopt()->lockForUpdate()->first();
            $kasus = $extension->kasus()->lockForUpdate()->first();

            $this->assertReviewable($extension, $assignment, $kasus);

            $extension->update([
                'status' => PerpanjanganPenugasan::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);

            Log::info('Permintaan perpanjangan penugasan ditolak.', [
                'extension_id' => $extension->id,
                'kasus_id' => $extension->kasus_id,
                'reviewed_by' => $reviewer->id,
            ]);

            return $extension->fresh(['penugasanPopt', 'kasus', 'reviewer']);
        });
    }

    public function cancelPendingForAssignment(PenugasanPopt $assignment, User $actor): int
    {
        return $assignment->extensionRequests()
            ->where('status', PerpanjanganPenugasan::STATUS_PENDING)
            ->update([
                'status' => PerpanjanganPenugasan::STATUS_CANCELLED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => 'Permintaan dibatalkan karena penugasan POPT diganti.',
            ]);
    }

    private function lockExtension(PerpanjanganPenugasan $extension): PerpanjanganPenugasan
    {
        return PerpanjanganPenugasan::query()
            ->lockForUpdate()
            ->findOrFail($extension->id);
    }

    private function assertReviewable(
        PerpanjanganPenugasan $extension,
        ?PenugasanPopt $assignment,
        ?KasusPenanganan $kasus,
    ): void {
        if ($extension->status !== PerpanjanganPenugasan::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'extension_id' => 'Permintaan perpanjangan ini sudah ditinjau sebelumnya.',
            ]);
        }

        if ($assignment === null || $assignment->status !== PenugasanPopt::STATUS_AKTIF) {
            throw ValidationException::withMessages([
                'extension_id' => 'Penugasan POPT sudah tidak aktif.',
            ]);
        }

        if ($kasus === null || $kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'extension_id' => 'Kasus yang sudah selesai tidak dapat diproses lagi.',
            ]);
        }
    }
}
