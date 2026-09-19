<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Canonical backend mapping from technical case state to monitoring state.
 *
 * The result is derived at read time. No monitoring state is persisted and
 * current_status remains the existing canonical handling status.
 */
class MonitoringStatusService
{
    public const STATUS_MENUNGGU_PENANGANAN = 'menunggu_penanganan';

    public const STATUS_DALAM_PENANGANAN = 'dalam_penanganan';

    public const STATUS_DITUNDA = 'ditunda';

    public const STATUS_MELEWATI_BATAS_WAKTU = 'melewati_batas_waktu';

    public const STATUS_SELESAI = 'selesai';

    /** @var array<string, string> */
    private const LABELS = [
        self::STATUS_MENUNGGU_PENANGANAN => 'Menunggu Penanganan',
        self::STATUS_DALAM_PENANGANAN => 'Dalam Penanganan',
        self::STATUS_DITUNDA => 'Ditunda',
        self::STATUS_MELEWATI_BATAS_WAKTU => 'Melewati Batas Waktu',
        self::STATUS_SELESAI => 'Selesai',
    ];

    /**
     * @return array{
     *     key:string,
     *     label:string,
     *     is_overdue:bool,
     *     effective_deadline_at:?CarbonInterface,
     *     overdue_since:?CarbonInterface
     * }
     */
    public function resolve(KasusPenanganan $kasus, ?CarbonInterface $now = null): array
    {
        $assignment = $kasus->relationLoaded('penugasanAktif')
            ? $kasus->penugasanAktif
            : $kasus->penugasanAktif()->first();
        if ($assignment === null && $kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
            $assignment = $kasus->relationLoaded('penugasanTerakhir')
                ? $kasus->penugasanTerakhir
                : $kasus->penugasanTerakhir()->first();
        }
        $deadline = $assignment?->deadline_at;
        $currentTime = $now ?? now();
        $isOverdue = $kasus->current_status !== KasusPenanganan::STATUS_SELESAI
            && $deadline !== null
            && $currentTime->greaterThan($deadline);

        $key = match (true) {
            $kasus->current_status === KasusPenanganan::STATUS_SELESAI => self::STATUS_SELESAI,
            $isOverdue => self::STATUS_MELEWATI_BATAS_WAKTU,
            $kasus->current_status === KasusPenanganan::STATUS_DITUNDA => self::STATUS_DITUNDA,
            in_array($kasus->current_status, [
                KasusPenanganan::STATUS_DITERIMA,
                KasusPenanganan::STATUS_DITUGASKAN,
            ], true) => self::STATUS_MENUNGGU_PENANGANAN,
            in_array($kasus->current_status, [
                KasusPenanganan::STATUS_SEDANG_DIREVIEW,
                KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
                KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
            ], true) => self::STATUS_DALAM_PENANGANAN,
            default => self::STATUS_MENUNGGU_PENANGANAN,
        };

        return [
            'key' => $key,
            'label' => self::LABELS[$key],
            'is_overdue' => $isOverdue,
            'effective_deadline_at' => $deadline,
            'overdue_since' => $isOverdue ? $deadline : null,
        ];
    }

    public function label(string $key): ?string
    {
        return self::LABELS[$key] ?? null;
    }

    /** @return array<string, string> */
    public function labels(): array
    {
        return self::LABELS;
    }

    /** Apply the canonical monitoring semantics to a server-side query. */
    public function applyFilter(Builder $query, ?string $key, ?CarbonInterface $now = null): Builder
    {
        if ($key === null || $key === '') {
            return $query;
        }

        $now = $now ?? now();
        $withoutOverdue = function (Builder $builder) use ($now): void {
            $builder->whereDoesntHave(
                'penugasanAktif',
                fn (Builder $assignment) => $assignment->whereNotNull('deadline_at')->where('deadline_at', '<', $now)
            );
        };
        $withOverdue = function (Builder $builder) use ($now): void {
            $builder->whereHas(
                'penugasanAktif',
                fn (Builder $assignment) => $assignment->whereNotNull('deadline_at')->where('deadline_at', '<', $now)
            );
        };

        return match ($key) {
            self::STATUS_SELESAI => $query->where('current_status', KasusPenanganan::STATUS_SELESAI),
            self::STATUS_MELEWATI_BATAS_WAKTU => $query
                ->where('current_status', '!=', KasusPenanganan::STATUS_SELESAI)
                ->tap($withOverdue),
            self::STATUS_DITUNDA => $query
                ->where('current_status', KasusPenanganan::STATUS_DITUNDA)
                ->tap($withoutOverdue),
            self::STATUS_MENUNGGU_PENANGANAN => $query
                ->whereIn('current_status', [KasusPenanganan::STATUS_DITERIMA, KasusPenanganan::STATUS_DITUGASKAN])
                ->tap($withoutOverdue),
            self::STATUS_DALAM_PENANGANAN => $query
                ->whereIn('current_status', [
                    KasusPenanganan::STATUS_SEDANG_DIREVIEW,
                    KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
                    KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
                ])
                ->tap($withoutOverdue),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
