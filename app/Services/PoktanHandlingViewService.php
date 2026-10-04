<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\PerpanjanganPenugasan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Read model for the Poktan case detail page.
 *
 * Poktan sees a small, derived monitoring view. Technical workflow states
 * remain canonical in KasusPenanganan and are interpreted by
 * MonitoringStatusService.
 */
class PoktanHandlingViewService
{
    /** @var array<string, string> */
    private const EXTENSION_LABELS = [
        PerpanjanganPenugasan::STATUS_PENDING => 'Menunggu Persetujuan',
        PerpanjanganPenugasan::STATUS_APPROVED => 'Disetujui',
        PerpanjanganPenugasan::STATUS_REJECTED => 'Ditolak',
        PerpanjanganPenugasan::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public function __construct(
        private readonly MonitoringStatusService $monitoringStatus,
    ) {}

    /** @return array<string, mixed> */
    public function present(KasusPenanganan $kasus): array
    {
        $status = $this->status($kasus);
        $assignment = $kasus->penugasanAktif ?? $kasus->penugasanTerakhir;
        $deadline = $assignment?->deadline_at;

        return [
            'status' => $status,
            'assignment' => $assignment,
            'popt' => $assignment?->popt,
            'assigned_at' => $assignment?->assigned_at,
            'accepted_at' => $assignment?->accepted_at,
            'deadline_at' => $deadline,
            'time_label' => $this->timeLabel($status, $assignment),
            'acceptance_label' => $this->acceptanceLabel($assignment),
            'assignment_status_label' => match ($assignment?->status) {
                'aktif' => 'Penugasan Aktif',
                'selesai' => 'Penugasan Selesai',
                'dicabut' => 'Penugasan Dicabut',
                default => 'Belum Ditugaskan',
            },
            'progress_count' => $kasus->progress->count(),
            'progress_timeline' => $this->progressTimeline($kasus, $assignment),
            'extensions' => $this->extensions($kasus),
            'final_report' => $kasus->finalReport,
            'final_evidences' => $this->finalEvidences($kasus),
            'last_update_at' => $this->lastUpdateAt($kasus),
        ];
    }

    /** @return array<string, mixed> */
    public function status(KasusPenanganan $kasus): array
    {
        $status = $this->monitoringStatus->resolve($kasus);

        return $status + [
            'badge_class' => match ($status['key']) {
                MonitoringStatusService::STATUS_MELEWATI_BATAS_WAKTU => 'bg-red-50 text-red-700',
                MonitoringStatusService::STATUS_DITUNDA => 'bg-orange-50 text-orange-700',
                MonitoringStatusService::STATUS_DALAM_PENANGANAN => 'bg-[#176b45] text-white',
                MonitoringStatusService::STATUS_SELESAI => 'bg-[#173b29] text-white',
                default => 'bg-[#eef3ef] text-[#66746c]',
            },
        ];
    }

    private function timeLabel(array $status, ?object $assignment): string
    {
        if ($status['key'] === MonitoringStatusService::STATUS_SELESAI) {
            return 'Selesai';
        }

        if ($status['is_overdue']) {
            return 'Melewati Batas Waktu';
        }

        return $assignment?->deadline_at === null ? 'Belum tersedia' : 'Tepat Waktu';
    }

    private function acceptanceLabel(?object $assignment): string
    {
        if ($assignment === null) {
            return 'Belum ditugaskan';
        }

        if ($assignment->accepted_at === null) {
            return 'Menunggu POPT menerima penugasan.';
        }

        return 'Penugasan diterima POPT pada '
            .$assignment->accepted_at->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i').'.';
    }

    /** @return array<int, array<string, mixed>> */
    private function progressTimeline(KasusPenanganan $kasus, ?object $assignment): array
    {
        $entries = [];

        if ($assignment?->accepted_at !== null) {
            $entries[] = [
                'key' => 'assignment.accepted.'.$assignment->id,
                'label' => 'POPT menerima penugasan',
                'catatan' => null,
                'waktu' => $assignment->accepted_at,
                'actor' => $assignment->popt?->name,
                'id' => 0,
            ];
        }

        foreach ($kasus->progress as $progress) {
            $entries[] = [
                'key' => 'progress.'.$progress->id,
                'label' => 'Update progress',
                'catatan' => $progress->catatan,
                'waktu' => $progress->created_at,
                'actor' => $progress->actor?->name,
                'id' => $progress->id,
            ];
        }

        return collect($entries)
            ->sortBy(fn (array $entry): array => [
                $entry['waktu']?->getTimestamp() ?? PHP_INT_MAX,
                $entry['id'],
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function extensions(KasusPenanganan $kasus): array
    {
        return $kasus->extensionRequests
            ->map(fn (PerpanjanganPenugasan $extension): array => [
                'id' => $extension->id,
                'status' => $extension->status,
                'status_label' => self::EXTENSION_LABELS[$extension->status] ?? 'Status tidak tersedia',
                'current_deadline_at' => $extension->current_deadline_at,
                'proposed_deadline_at' => $extension->proposed_deadline_at,
                'reason' => $extension->reason,
                'created_at' => $extension->created_at,
                'reviewed_at' => $extension->reviewed_at,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function finalEvidences(KasusPenanganan $kasus): array
    {
        return $kasus->finalReport?->evidences
            ->map(fn ($evidence): array => [
                'file_name' => $evidence->file_name,
                'mime_type' => $evidence->mime_type,
                'url' => Storage::disk('public')->url($evidence->file_path),
            ])
            ->values()
            ->all() ?? [];
    }

    private function lastUpdateAt(KasusPenanganan $kasus): ?CarbonInterface
    {
        $timestamps = collect([
            $kasus->created_at,
            $kasus->finalReport?->submitted_at,
        ])
            ->merge($kasus->progress->pluck('created_at'))
            ->merge($kasus->riwayatStatus->pluck('created_at'))
            ->filter();

        return $timestamps->sortByDesc(fn ($timestamp) => $timestamp->getTimestamp())->first();
    }
}
