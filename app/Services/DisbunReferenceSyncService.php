<?php

namespace App\Services;

use App\Models\RefKomoditas;
use App\Models\RefKelompokTani;
use Illuminate\Support\Facades\DB;
/**
 * Fetches complete Disbun datasets first, then performs a non-destructive
 * local reference upsert. No local rows are deleted when a fetch is partial.
 */
final class DisbunReferenceSyncService
{
    public function __construct(
        private readonly HttpKomoditasReferensiClient $komoditasClient,
        private readonly HttpKelompokTaniReferensiClient $kelompokTaniClient,
        private readonly DisbunCommodityMapper $commodityMapper,
    ) {}

    /**
     * @return array{komoditas:array<string,mixed>,kelompok_tani:array<string,mixed>}
     */
    public function syncAllReferences(?callable $progress = null): array
    {
        // Fetch order is intentional: group commodity references are resolved
        // against the complete commodity dataset, never by raw numeric ID.
        $komoditas = $this->komoditasClient->fetchAllWithReport(
            fn (int $start, int $count): mixed => $progress?->__invoke('komoditas', $start, $count)
        );
        $kelompokTani = $this->kelompokTaniClient->fetchAllWithReport(
            fn (int $start, int $count): mixed => $progress?->__invoke('kelompok_tani', $start, $count)
        );

        return DB::transaction(function () use ($komoditas, $kelompokTani): array {
            $commodityStats = $this->upsertKomoditas($komoditas);
            $groupStats = $this->upsertKelompokTani($kelompokTani);

            return [
                'komoditas' => array_merge($this->fetchStats($komoditas), $commodityStats),
                'kelompok_tani' => array_merge($this->fetchStats($kelompokTani), $groupStats),
            ];
        });
    }

    /** @return array<string, mixed> */
    private function fetchStats(ReferenceFetchResult $result): array
    {
        return [
            'fetched' => $result->fetched,
            'valid' => $result->valid,
            'quarantined' => $result->quarantined,
            'pages' => $result->pages,
            'total' => $result->total,
            'count_all' => $result->countAll,
            'unique_external_ids' => $result->uniqueExternalIds,
            'duplicate_occurrences' => $result->duplicateOccurrences,
            'duplicate_external_id_count' => $result->duplicateExternalIdCount,
            'exact_duplicate_occurrences' => $result->exactDuplicateOccurrences,
            'exact_duplicate_ids' => $result->exactDuplicateIds,
            'conflicting_duplicate_ids' => $result->conflictingDuplicateIds,
            'conflicting_external_ids' => $result->conflictingExternalIds,
            'terminal_short_page_start' => $result->terminalShortPageStart,
            'source_exhausted_at' => $result->sourceExhaustedAt,
            'source_completion_ratio' => $result->sourceCompletionRatio,
            'metadata_gap' => $result->metadataGap,
            'source_warning' => $result->sourceWarning,
            'warning_reasons' => $result->warningReasons,
            'order' => $result->order,
            'pages_with_overlap' => $result->pagesWithOverlap,
            'total_overlapping_occurrences' => $result->totalOverlappingOccurrences,
            'quarantine_reasons' => $result->quarantineReasons,
            'unresolved_commodity' => 0,
            'local' => 0,
        ];
    }

    /** @return array<string, int> */
    private function upsertKomoditas(ReferenceFetchResult $result): array
    {
        $existing = RefKomoditas::query()->get()->all();
        $byExternal = collect($existing)
            ->filter(fn (RefKomoditas $row): bool => $row->source === RefKomoditas::SOURCE_DISBUN && $row->disbun_record_id !== null)
            ->keyBy(fn (RefKomoditas $row): string => (string) $row->disbun_record_id);
        $byCode = collect($existing)->filter(fn (RefKomoditas $row): bool => trim((string) $row->kode) !== '')
            ->keyBy(fn (RefKomoditas $row): string => $this->canonical($row->kode));
        $byName = collect($existing)->keyBy(fn (RefKomoditas $row): string => $this->canonical($row->nama));
        $now = now()->toDateTimeString();
        $payload = [];
        $upserted = 0;
        $updated = 0;

        foreach ($result->rows as $row) {
            $externalId = (string) $row['id'];
            $code = trim((string) ($row['kode'] ?? ''));
            $name = trim((string) $row['nama']);
            $match = $byExternal->get($externalId)
                ?? ($code !== '' ? $byCode->get($this->canonical($code)) : null)
                ?? $byName->get($this->canonical($name));
            $localCode = $code !== '' ? $code : 'DISBUN-'.$externalId;

            $payload[] = [
                'id' => $match?->id,
                'disbun_record_id' => (int) $row['id'],
                'source' => RefKomoditas::SOURCE_DISBUN,
                'kode' => $localCode,
                'nama' => $name,
                'nama_latin' => $row['nama_latin'] ?? null,
                'source_is_active' => (bool) ($row['is_active'] ?? true),
                'is_verified' => true,
                'sync_status' => RefKomoditas::SYNC_SYNCED,
                'quarantine_reason' => null,
                'last_synced_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ];

            $upserted++;
            $updated += $match !== null ? 1 : 0;
        }

        if ($payload !== []) {
            RefKomoditas::upsert(
                $payload,
                ['id'],
                ['disbun_record_id', 'source', 'kode', 'nama', 'nama_latin', 'source_is_active', 'is_verified', 'sync_status', 'quarantine_reason', 'last_synced_at', 'updated_at'],
            );
        }

        return ['upserted' => $upserted, 'updated' => $updated, 'local' => RefKomoditas::query()->where('source', RefKomoditas::SOURCE_DISBUN)->count()];
    }

    /** @return array<string, int> */
    private function upsertKelompokTani(ReferenceFetchResult $result): array
    {
        $commodities = RefKomoditas::query()->where('source', RefKomoditas::SOURCE_DISBUN)->get();
        $byName = $this->commodityMapper->index($commodities);
        $existing = RefKelompokTani::query()->where('source', RefKelompokTani::SOURCE_DISBUN)->get()
            ->keyBy(fn (RefKelompokTani $row): string => (string) $row->disbun_record_id);
        $now = now()->toDateTimeString();
        $payload = [];
        $updated = 0;
        $unresolved = 0;

        foreach ($result->rows as $row) {
            $externalId = (string) $row['id'];
            $name = trim((string) ($row['jenis_komoditi'] ?? $row['_external_commodity_name'] ?? ''));
            $commodity = $name !== '' ? $this->commodityMapper->resolve($name, $byName) : null;
            $hasExternalCommodity = ($row['_external_commodity_id'] ?? null) !== null || $name !== '';
            $mappingStatus = $hasExternalCommodity ? ($commodity === null ? 'unresolved' : 'mapped') : null;
            if ($mappingStatus === 'unresolved') $unresolved++;

            $payload[] = [
                'disbun_record_id' => $externalId,
                'source' => RefKelompokTani::SOURCE_DISBUN,
                'kode' => trim((string) ($row['kode'] ?? '')) ?: null,
                'kode_kelompok' => trim((string) ($row['kode_kelompok'] ?? $row['kode'] ?? '')) ?: null,
                'nama' => trim((string) $row['nama']),
                'ketua' => $this->nullableString($row['ketua'] ?? null),
                'kabupaten' => $this->nullableString($row['kabupaten'] ?? null),
                'kecamatan' => $this->nullableString($row['kecamatan'] ?? null),
                'desa' => $this->nullableString($row['desa'] ?? null),
                'kelurahan' => $this->nullableString($row['kelurahan'] ?? $row['desa'] ?? null),
                'kode_kabupaten' => $this->nullableString($row['kode_kabupaten'] ?? null),
                'kode_kecamatan' => $this->nullableString($row['kode_kecamatan'] ?? null),
                'kode_desa' => $this->nullableString($row['kode_desa'] ?? null),
                'jenis_komoditi' => $this->nullableString($row['jenis_komoditi'] ?? null),
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'status' => $this->nullableString($row['status'] ?? null),
                'deleted_at' => $this->nullableString($row['deleted_at'] ?? null),
                'external_commodity_id' => isset($row['_external_commodity_id']) ? (string) $row['_external_commodity_id'] : null,
                'external_commodity_code' => null,
                'external_commodity_name' => $name !== '' ? $name : null,
                'commodity_ref_id' => $commodity?->id,
                'commodity_mapping_status' => $mappingStatus,
                'source_is_active' => (bool) ($row['is_active'] ?? true),
                'is_verified' => true,
                'sync_status' => RefKelompokTani::SYNC_SYNCED,
                'quarantine_reason' => null,
                'source_updated_at' => $row['source_updated_at'] ?? null,
                'last_synced_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            $updated += $existing->has($externalId) ? 1 : 0;
        }

        if ($payload !== []) {
            RefKelompokTani::upsert(
                $payload,
                ['source', 'disbun_record_id'],
                ['kode', 'kode_kelompok', 'nama', 'ketua', 'kabupaten', 'kecamatan', 'desa', 'kelurahan', 'kode_kabupaten', 'kode_kecamatan', 'kode_desa', 'jenis_komoditi', 'latitude', 'longitude', 'status', 'deleted_at', 'external_commodity_id', 'external_commodity_code', 'external_commodity_name', 'commodity_ref_id', 'commodity_mapping_status', 'source_is_active', 'is_verified', 'sync_status', 'quarantine_reason', 'source_updated_at', 'last_synced_at', 'updated_at'],
            );
        }

        return [
            'upserted' => count($payload),
            'updated' => $updated,
            'unresolved_commodity' => $unresolved,
            'local' => RefKelompokTani::query()->where('source', RefKelompokTani::SOURCE_DISBUN)->count(),
        ];
    }

    private function canonical(mixed $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
