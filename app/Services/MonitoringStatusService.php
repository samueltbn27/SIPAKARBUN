<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PermohonanPenanganan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/** Four derived states. Technical states and append-only history stay intact. */
class MonitoringStatusService
{
    public const STATUS_MENUNGGU_REVIEW = 'menunggu_review';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DALAM_PENANGANAN = 'dalam_penanganan';

    public const STATUS_SELESAI = 'selesai';

    public function resolve(KasusPenanganan $kasus, ?CarbonInterface $now = null): array
    {
        $issues = $this->handlingIssues($kasus);
        $key = $kasus->current_status === KasusPenanganan::STATUS_SELESAI
            ? self::STATUS_SELESAI
            : ($issues === [] ? self::STATUS_DALAM_PENANGANAN : self::STATUS_MENUNGGU_REVIEW);
        $assignment = $kasus->penugasanAktif;
        if ($assignment === null && $key === self::STATUS_SELESAI) {
            $assignment = $kasus->penugasanTerakhir;
        }
        $deadline = $this->assignmentDeadline($assignment);
        $isOverdue = $key === self::STATUS_DALAM_PENANGANAN
            && $deadline !== null && ($now ?? now())->greaterThan($deadline);
        $isLegacyPostponed = $kasus->current_status === KasusPenanganan::STATUS_DITUNDA;

        return $this->presentation($key) + [
            'is_overdue' => $isOverdue,
            'effective_deadline_at' => $deadline,
            'overdue_since' => $isOverdue ? $deadline : null,
            'legacy_status' => $isLegacyPostponed ? KasusPenanganan::STATUS_DITUNDA : null,
            'legacy_note' => $isLegacyPostponed
                ? 'Catatan data lama: penanganan pernah ditunda. Status teknis dan riwayat penundaan tetap tersimpan.' : null,
            'legacy_reason' => $isLegacyPostponed
                ? $kasus->riwayatStatus->firstWhere('status', KasusPenanganan::STATUS_DITUNDA)?->catatan : null,
            // Completed history remains completed; never invent missing proof.
            'completion_evidence_missing' => $key === self::STATUS_SELESAI
                && ($kasus->finalReport === null || $kasus->finalReport->evidences->isEmpty()),
            ...$this->historyWarning($issues),
        ];
    }

    public function resolveRequest(PermohonanPenanganan $permohonan, ?CarbonInterface $now = null): array
    {
        if ($permohonan->kasus !== null) {
            $permohonan->kasus->setRelation('permohonan', $permohonan);

            return $this->resolve($permohonan->kasus, $now);
        }

        $key = $permohonan->status === PermohonanPenanganan::STATUS_DITOLAK
            ? self::STATUS_DITOLAK : self::STATUS_MENUNGGU_REVIEW;

        return $this->presentation($key) + [
            'is_overdue' => false,
            'effective_deadline_at' => null,
            'overdue_since' => null,
            'legacy_status' => null,
            'legacy_note' => null,
            'legacy_reason' => null,
            'completion_evidence_missing' => false,
            ...$this->historyWarning($permohonan->status === PermohonanPenanganan::STATUS_DITERIMA
                ? ['Permohonan tercatat diterima, tetapi kasus dan penugasan belum tersedia.'] : []),
        ];
    }

    /** Labels for historical technical events, not a claim about field work. */
    public function caseKey(string $technicalStatus): string
    {
        return match ($technicalStatus) {
            KasusPenanganan::STATUS_SELESAI => self::STATUS_SELESAI,
            KasusPenanganan::STATUS_DITUGASKAN,
            KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
            KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
            KasusPenanganan::STATUS_DITUNDA => self::STATUS_DALAM_PENANGANAN,
            default => self::STATUS_MENUNGGU_REVIEW,
        };
    }

    public function presentation(string $key): array
    {
        $definition = config('kasus.monitoring.'.$key, []);

        return [
            'key' => $key,
            'label' => $definition['label'] ?? $key,
            'badge_class' => $definition['badgeClass'] ?? 'bg-gray-100 text-gray-600',
            'icon_path' => $definition['iconPath'] ?? null,
        ];
    }

    public function label(string $key): ?string
    {
        return $this->labels()[$key] ?? null;
    }

    public function labels(): array
    {
        return array_map(fn (array $definition): string => $definition['label'], config('kasus.monitoring'));
    }

    public function activeStatuses(): array
    {
        return array_keys(array_filter(config('kasus.monitoring'), fn (array $definition): bool => $definition['isActiveHandling']));
    }

    /** Primary filters and the read projection use the same prerequisites. */
    public function applyFilter(Builder $query, ?string $key): Builder
    {
        return match ($key) {
            null, '' => $query,
            self::STATUS_SELESAI => $query->where('current_status', KasusPenanganan::STATUS_SELESAI),
            self::STATUS_DALAM_PENANGANAN => $this->handlingQuery($query),
            self::STATUS_MENUNGGU_REVIEW => $query
                ->where('current_status', '!=', KasusPenanganan::STATUS_SELESAI)
                ->whereNotIn('id', $this->handlingQuery(KasusPenanganan::query())->select('id')),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function applyOverdueFilter(Builder $query, ?CarbonInterface $now = null): Builder
    {
        return $this->handlingQuery($query)
            ->whereHas('penugasanAktif', fn (Builder $assignment) => $assignment
                ->where('deadline_at', '<', $now ?? now()));
    }

    public function applyRequestFilter(Builder $query, ?string $key): Builder
    {
        if ($key === null || $key === '') {
            return $query;
        }

        return $query->where(function (Builder $match) use ($key): void {
            $match->whereHas('kasus', fn (Builder $case) => $this->applyFilter($case, $key));
            $requestStatuses = match ($key) {
                self::STATUS_MENUNGGU_REVIEW => [PermohonanPenanganan::STATUS_DIAJUKAN, PermohonanPenanganan::STATUS_SEDANG_DIREVIEW, PermohonanPenanganan::STATUS_DITERIMA],
                self::STATUS_DITOLAK => [PermohonanPenanganan::STATUS_DITOLAK],
                default => [],
            };
            if ($requestStatuses !== []) {
                $match->orWhere(fn (Builder $request) => $request->doesntHave('kasus')
                    ->whereIn('permohonan_penanganan.status', $requestStatuses));
            }
        });
    }

    private function handlingQuery(Builder $query): Builder
    {
        return $query->where('current_status', '!=', KasusPenanganan::STATUS_SELESAI)
            ->whereHas('permohonan', fn (Builder $request) => $request
                ->where('status', PermohonanPenanganan::STATUS_DITERIMA)
                ->whereHas('keputusan', fn (Builder $decision) => $decision
                    ->where('keputusan', KeputusanPermohonan::KEPUTUSAN_DITERIMA)))
            ->whereHas('penugasanAktif', fn (Builder $assignment) => $assignment
                ->has('popt')->whereNotNull('deadline_at')->whereDate('deadline_at', '>=', '0001-01-01'));
    }

    private function handlingIssues(KasusPenanganan $kasus): array
    {
        if ($kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
            return [];
        }
        $issues = [];
        $request = $kasus->permohonan;
        if ($request?->status !== PermohonanPenanganan::STATUS_DITERIMA
            || $request?->keputusan?->keputusan !== KeputusanPermohonan::KEPUTUSAN_DITERIMA) {
            $issues[] = 'Persetujuan Operator belum tercatat lengkap atau tidak sesuai dengan kasus.';
        }
        if ($kasus->penugasanAktif?->popt === null) {
            $issues[] = 'Penugasan POPT aktif belum tersedia.';
        }
        if ($this->assignmentDeadline($kasus->penugasanAktif) === null) {
            $issues[] = 'Target penyelesaian aktif yang valid belum tersedia.';
        }

        return $issues;
    }

    /** Historical invalid targets are exposed as missing, without rewriting them. */
    public function assignmentDeadline(?object $assignment): ?CarbonInterface
    {
        try {
            return $assignment?->deadline_at;
        } catch (Throwable) {
            return null;
        }
    }

    private function historyWarning(array $issues): array
    {
        return [
            'history_incomplete' => $issues !== [],
            'history_issues' => $issues,
            'history_note' => $issues === [] ? null
                : 'Data historis perlu ditinjau Operator. '.implode(' ', $issues).' Riwayat yang tersedia tetap dipertahankan.',
        ];
    }
}
