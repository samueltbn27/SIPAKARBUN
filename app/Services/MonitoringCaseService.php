<?php

namespace App\Services;

use App\Models\PermohonanPenanganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * One monitoring entry per request, enriched by its case after acceptance.
 * The case's immutable snapshots/coordinates take precedence once it exists.
 * Archived accepted cases stay excluded; cancelled requests returned to review
 * can reappear as requests, without resurrecting the archived handling case.
 */
class MonitoringCaseService
{
    public function __construct(private readonly MonitoringStatusService $status) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with([
                'diagnosis.results', 'keputusan',
                'kasus.permohonan.keputusan', 'kasus.penugasanAktif.popt', 'kasus.penugasanTerakhir.popt',
                'kasus.progressTerakhir', 'kasus.riwayatStatus', 'kasus.finalReport.evidences',
            ])
            ->orderByDesc('monitoring_recorded_at')
            ->orderByDesc('permohonan_penanganan.id')
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }

    public function summary(array $filters = []): array
    {
        $query = $this->query($filters);
        $counts = [];
        foreach (array_keys($this->status->labels()) as $key) {
            $counts[$key] = $this->status->applyRequestFilter(clone $query, $key)->count();
        }

        return [
            'total' => (clone $query)->count(),
            'review' => $counts[MonitoringStatusService::STATUS_MENUNGGU_REVIEW],
            'active' => array_sum(array_intersect_key($counts, array_flip($this->status->activeStatuses()))),
            'completed' => $counts[MonitoringStatusService::STATUS_SELESAI],
            'overdue' => (clone $query)->whereHas('kasus', fn (Builder $case) => $this->status->applyOverdueFilter($case))->count(),
            'by_status' => $counts,
        ];
    }

    public function filterOptions(): array
    {
        return [
            'regencies' => $this->values('permohonan_penanganan.kabupaten'),
            'commodities' => $this->values($this->commodityExpression()),
            'diseases' => $this->values('CASE WHEN monitoring_case.id IS NOT NULL THEN monitoring_case.penyakit_name_snapshot ELSE (SELECT disease_name_snapshot FROM diagnosis_results WHERE diagnosis_results.diagnosis_id = permohonan_penanganan.diagnosis_id ORDER BY ranking, id LIMIT 1) END'),
        ];
    }

    private function values(string $expression)
    {
        return $this->query()->select([])->selectRaw($expression.' as monitoring_value')
            ->distinct()->pluck('monitoring_value')->filter(fn ($value) => $value !== null && $value !== '')
            ->sort()->values();
    }

    private function commodityExpression(): string
    {
        return 'CASE WHEN monitoring_case.id IS NOT NULL THEN monitoring_case.komoditas_name_snapshot ELSE monitoring_commodity.nama END';
    }

    private function query(array $filters = []): Builder
    {
        $query = PermohonanPenanganan::query()
            ->leftJoin('kasus_penanganan as monitoring_case', function ($join): void {
                $join->on('monitoring_case.permohonan_id', '=', 'permohonan_penanganan.id')
                    ->whereNull('monitoring_case.deleted_at');
            })
            ->leftJoin('diagnoses as monitoring_diagnosis', 'monitoring_diagnosis.id', '=', 'permohonan_penanganan.diagnosis_id')
            ->leftJoin('ref_komoditas as monitoring_commodity', 'monitoring_commodity.id', '=', 'monitoring_diagnosis.commodity_id')
            ->select('permohonan_penanganan.*')
            ->selectRaw('COALESCE(monitoring_case.created_at, permohonan_penanganan.created_at) as monitoring_recorded_at')
            ->selectRaw('monitoring_commodity.nama as monitoring_commodity_name, monitoring_commodity.kode as monitoring_commodity_code')
            ->where(function (Builder $scope): void {
                $scope->whereNotNull('monitoring_case.id')
                    ->orWhere(function (Builder $request): void {
                        $request->whereNull('monitoring_case.id')->where(function (Builder $visible): void {
                            $visible->whereIn('permohonan_penanganan.status', [
                                PermohonanPenanganan::STATUS_DIAJUKAN,
                                PermohonanPenanganan::STATUS_SEDANG_DIREVIEW,
                                PermohonanPenanganan::STATUS_DITOLAK,
                            ])->orWhere(fn (Builder $accepted) => $accepted
                                ->where('permohonan_penanganan.status', PermohonanPenanganan::STATUS_DITERIMA)
                                ->whereDoesntHave('kasus', fn (Builder $case) => $case->withTrashed()));
                        });
                    });
            });

        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (! empty($filters[$key])) {
                $query->whereDate(DB::raw('COALESCE(monitoring_case.created_at, permohonan_penanganan.created_at)'), $operator, $filters[$key]);
            }
        }
        if (! empty($filters['regency'])) {
            $query->where('permohonan_penanganan.kabupaten', $filters['regency']);
        }
        if (! empty($filters['commodity'])) {
            $query->whereRaw($this->commodityExpression().' = ?', [$filters['commodity']]);
        }
        if (! empty($filters['disease'])) {
            $query->where(function (Builder $disease) use ($filters): void {
                $disease->where('monitoring_case.penyakit_name_snapshot', $filters['disease'])
                    ->orWhere(fn (Builder $request) => $request->whereNull('monitoring_case.id')
                        ->whereHas('diagnosis.results', fn (Builder $result) => $result
                            ->where('ranking', 1)->where('disease_name_snapshot', $filters['disease'])));
            });
        }
        $this->status->applyRequestFilter($query, $filters['status'] ?? null);
        if (! empty($filters['overdue'])) {
            $query->whereHas('kasus', fn (Builder $case) => $this->status->applyOverdueFilter($case));
        }

        return $query;
    }
}
