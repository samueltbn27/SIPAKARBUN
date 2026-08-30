<?php

namespace App\Services;

use App\Contracts\KelompokTaniReferensiClient;
use App\Exceptions\DisbunReferenceSyncException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads the actual Disbun kelompok-tani contract:
 * payload.data.result.data with result.count/start/limit pagination metadata.
 */
class HttpKelompokTaniReferensiClient implements KelompokTaniReferensiClient
{
    private const DUPLICATE_COMPARISON_FIELDS = [
        'id',
        'kode_kelompok',
        'nama_kelompok',
        'jenis_komoditi',
        'kode_kabupaten',
        'kode_kecamatan',
        'kode_desa',
        'kabupaten',
        'kecamatan',
        'kelurahan',
        'latitude',
        'longitude',
        'updated_at',
    ];

    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $token = '',
        private readonly int $timeout = 30,
        private readonly int $pageSize = 50,
        private readonly int $maxPages = 250,
        private readonly string $userAgent = 'SIPAKARBUN/1.0',
        private readonly int $pageDelayMs = 750,
        private readonly int $rateLimitRetries = 3,
        private readonly int $rateLimitBackoffMs = 60000,
        private readonly float $sourceExhaustionWarningRatio = 0.90,
    ) {}

    public function all(): array
    {
        try {
            return $this->fetchAllWithReport()->rows;
        } catch (Throwable $e) {
            Log::error('Exception saat panggil API Disbun kelompok tani', ['message' => $e->getMessage()]);

            return [];
        }
    }

    public function fetchAllWithReport(?callable $progress = null): ReferenceFetchResult
    {
        $rows = [];
        $seen = [];
        $rawOccurrences = [];
        $fetched = 0;
        $valid = 0;
        $quarantined = 0;
        $reasons = [];
        $start = 0;
        $pagesFetched = 0;
        $total = null;
        $countAll = null;
        $reportedLimit = null;
        $order = null;
        $terminalShortPageStart = null;
        $sourceExhaustedAt = null;
        $sourceCompletionRatio = null;
        $previousPageIds = null;
        $pagesWithOverlap = 0;
        $totalOverlappingOccurrences = 0;

        while (true) {
            if ($pagesFetched >= $this->maxPages()) {
                throw new DisbunReferenceSyncException('Pagination kelompok tani melewati batas maksimum '.$this->maxPages().' halaman.');
            }

            $payload = $this->fetchPage($start);
            $pagesFetched++;
            $progress?->__invoke($start, count($payload['data']));

            if ($total === null) {
                $total = $payload['total'];
                $countAll = $payload['count_all'];
                $reportedLimit = $payload['response_limit'];
                $order = $payload['order'];
            } elseif ($total !== $payload['total']
                || $countAll !== $payload['count_all']
                || $reportedLimit !== $payload['response_limit']
                || $order !== $payload['order']) {
                throw new DisbunReferenceSyncException("Metadata kelompok tani berubah pada start={$start}.");
            }

            $pageCount = count($payload['data']);
            if ($terminalShortPageStart !== null && $pageCount > 0) {
                throw new DisbunReferenceSyncException("Halaman setelah short page kelompok tani tidak kosong pada start={$start}.");
            }

            $currentPageIds = [];
            foreach ($payload['data'] as $raw) {
                $fetched++;
                if (! is_array($raw)) {
                    $quarantined++;
                    $reasons['invalid_payload'] = ($reasons['invalid_payload'] ?? 0) + 1;
                    continue;
                }

                if (isset($raw['id']) && trim((string) $raw['id']) !== '') {
                    $externalId = (string) $raw['id'];
                    $currentPageIds[] = $externalId;
                    $rawOccurrences[$externalId][] = $this->duplicateComparisonPayload($raw);
                }

                $normalized = $this->normalize($raw);
                if ($normalized === null) {
                    $quarantined++;
                    $reason = $this->invalidReason($raw);
                    $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
                    continue;
                }

                $valid++;
                if (! isset($seen[(string) $normalized['id']])) {
                    $seen[(string) $normalized['id']] = true;
                    $rows[] = $normalized;
                }
            }

            $currentPageIds = array_values(array_unique($currentPageIds));
            if ($previousPageIds !== null) {
                $overlapCount = count(array_intersect($previousPageIds, $currentPageIds));
                if ($overlapCount > 0) {
                    $pagesWithOverlap++;
                    $totalOverlappingOccurrences += $overlapCount;
                }
            }
            $previousPageIds = $currentPageIds;

            if ($pageCount === 0) {
                if ($total === 0) {
                    break;
                }

                if ($terminalShortPageStart === null) {
                    throw new DisbunReferenceSyncException("Halaman kosong kelompok tani muncul terlalu awal pada start={$start}.");
                }

                // Completeness is established by the short page followed by
                // this successful empty confirmation page. The ratio is only
                // diagnostic because Disbun metadata is known to over-report.
                $sourceCompletionRatio = $fetched / $total;
                $sourceExhaustedAt = $start;
                break;
            }

            if ($pageCount < $payload['response_limit']) {
                $terminalShortPageStart = $start;
            } elseif ($payload['next_start'] >= $total) {
                break;
            }

            if ($payload['next_start'] <= $start) {
                throw new DisbunReferenceSyncException("Pagination kelompok tani tidak maju pada start={$start}.");
            }

            $this->delayBeforeNextPage();
            $start = $payload['next_start'];
        }

        if ($fetched > $total) {
            throw new DisbunReferenceSyncException("Pagination kelompok tani melebihi metadata: fetched={$fetched}, expected={$total}.");
        }

        if ($fetched !== $total && $sourceExhaustedAt === null) {
            throw new DisbunReferenceSyncException("Pagination kelompok tani tidak lengkap: fetched={$fetched}, expected={$total}.");
        }

        $duplicateOccurrences = 0;
        $duplicateExternalIdCount = 0;
        $exactDuplicateOccurrences = 0;
        $exactDuplicateIds = 0;
        $conflictingExternalIds = [];
        foreach ($rawOccurrences as $externalId => $payloads) {
            if (count($payloads) < 2) {
                continue;
            }

            $duplicateExternalIdCount++;
            $occurrences = count($payloads) - 1;
            $duplicateOccurrences += $occurrences;
            $fingerprints = array_unique(array_map(
                static fn (array $payload): string => json_encode($payload, JSON_THROW_ON_ERROR),
                $payloads,
            ));
            if (count($fingerprints) === 1) {
                $exactDuplicateIds++;
                $exactDuplicateOccurrences += $occurrences;
            } else {
                $conflictingExternalIds[] = (string) $externalId;
            }
        }

        if ($conflictingExternalIds !== []) {
            throw new DisbunReferenceSyncException(
                'Conflicting duplicate external ID kelompok tani: '.implode(', ', array_slice($conflictingExternalIds, 0, 10)).'.'
            );
        }

        $metadataGap = max(0, $total - $fetched);
        $warningReasons = [];
        if ($metadataGap > 0) {
            $warningReasons[] = 'metadata_count_mismatch';
        }
        if ($duplicateOccurrences > 0) {
            $warningReasons[] = 'exact_duplicate_source_rows';
        }
        if ($pagesWithOverlap > 0) {
            $warningReasons[] = 'page_overlap';
        }
        if ($sourceCompletionRatio !== null
            && $sourceCompletionRatio < $this->sourceExhaustionWarningRatio()) {
            $warningReasons[] = 'low_source_completion_ratio';
        }

        return new ReferenceFetchResult(
            rows: $rows,
            fetched: $fetched,
            valid: $valid,
            quarantined: $quarantined,
            pages: $pagesFetched,
            quarantineReasons: $reasons,
            total: $total,
            countAll: $countAll,
            uniqueExternalIds: count($rawOccurrences),
            duplicateOccurrences: $duplicateOccurrences,
            duplicateExternalIdCount: $duplicateExternalIdCount,
            exactDuplicateOccurrences: $exactDuplicateOccurrences,
            exactDuplicateIds: $exactDuplicateIds,
            conflictingDuplicateIds: count($conflictingExternalIds),
            conflictingExternalIds: $conflictingExternalIds,
            terminalShortPageStart: $terminalShortPageStart,
            sourceExhaustedAt: $sourceExhaustedAt,
            sourceCompletionRatio: $sourceCompletionRatio,
            metadataGap: $metadataGap,
            sourceWarning: $warningReasons !== [],
            warningReasons: $warningReasons,
            order: $order,
            pagesWithOverlap: $pagesWithOverlap,
            totalOverlappingOccurrences: $totalOverlappingOccurrences,
        );
    }

    /**
     * Fetches every metadata-addressed page without mutating local data.
     * Unlike the sync path, this audit deliberately continues after a short
     * or empty page so later offsets can still be inspected.
     *
     * @return array<string, mixed>
     */
    public function auditPagination(?callable $progress = null): array
    {
        $requestedLimit = $this->limit();
        $requestedStart = 0;
        $initialCount = null;
        $initialCountAll = null;
        $initialOrder = null;
        $pages = [];
        $pageIds = [];
        $occurrences = [];
        $rawFetched = 0;
        $metadataChanges = [];
        $duplicatedPagePairs = [];
        $firstAnomalousStart = null;
        $requestFailure = null;

        while (count($pages) < $this->maxPages()) {
            try {
                $payload = $this->requestPage($requestedStart, enforceRequestedStart: false);
            } catch (Throwable $e) {
                $requestFailure = [
                    'requested_start' => $requestedStart,
                    'message' => $e->getMessage(),
                ];
                $firstAnomalousStart ??= $requestedStart;
                break;
            }

            $initialCount ??= $payload['total'];
            $initialCountAll ??= $payload['count_all'];
            $initialOrder ??= $payload['order'];

            if ($payload['total'] !== $initialCount
                || $payload['count_all'] !== $initialCountAll
                || $payload['order'] !== $initialOrder) {
                $metadataChanges[] = [
                    'requested_start' => $requestedStart,
                    'count' => $payload['total'],
                    'count_all' => $payload['count_all'],
                    'order' => $payload['order'],
                ];
                $firstAnomalousStart ??= $requestedStart;
            }

            $ids = [];
            foreach ($payload['data'] as $row) {
                $rawFetched++;
                if (! is_array($row) || ! isset($row['id']) || trim((string) $row['id']) === '') {
                    continue;
                }

                $externalId = (string) $row['id'];
                $ids[] = $externalId;
                $occurrences[$externalId][] = [
                    'requested_start' => $requestedStart,
                    'payload' => $this->duplicateComparisonPayload($row),
                ];
            }

            $recordsReturned = count($payload['data']);
            $page = [
                'requested_start' => $requestedStart,
                'requested_limit' => $requestedLimit,
                'response_start' => $payload['response_start'],
                'response_limit' => $payload['response_limit'],
                'response_count' => $payload['total'],
                'response_count_all' => $payload['count_all'],
                'records_returned' => $recordsReturned,
                'first_external_id' => $ids[0] ?? null,
                'last_external_id' => $ids === [] ? null : $ids[array_key_last($ids)],
                'order' => $payload['order'],
            ];
            $currentUniqueIds = array_values(array_unique($ids));
            $previousStart = $pages === [] ? null : $pages[array_key_last($pages)]['requested_start'];
            if ($previousStart !== null && $currentUniqueIds !== [] && $currentUniqueIds === ($pageIds[$previousStart] ?? [])) {
                $duplicatedPagePairs[] = [
                    'left_start' => $previousStart,
                    'right_start' => $requestedStart,
                ];
                $firstAnomalousStart ??= $requestedStart;
            }
            $pages[] = $page;
            $pageIds[$requestedStart] = $currentUniqueIds;
            $progress?->__invoke($page);

            $nextStart = $requestedStart + $requestedLimit;
            $isBeforeLastExpectedPage = $initialCount !== null && $nextStart < $initialCount;
            if ($payload['response_start'] !== $requestedStart
                || $payload['response_limit'] !== $requestedLimit
                || ($recordsReturned < $requestedLimit && $isBeforeLastExpectedPage)) {
                $firstAnomalousStart ??= $requestedStart;
            }

            if ($initialCount === 0 || $nextStart >= $initialCount) {
                break;
            }

            $this->delayBeforeNextPage();
            $requestedStart = $nextStart;
        }

        if ($initialCount !== null && count($pages) >= $this->maxPages()
            && ($requestedStart + $requestedLimit) < $initialCount) {
            $requestFailure = [
                'requested_start' => $requestedStart + $requestedLimit,
                'message' => 'Pagination audit melewati batas maksimum '.$this->maxPages().' halaman.',
            ];
            $firstAnomalousStart ??= $requestedStart + $requestedLimit;
        }

        $duplicates = [];
        $duplicateOccurrences = 0;
        $exactDuplicateIds = 0;
        $conflictingDuplicateIds = 0;
        foreach ($occurrences as $externalId => $items) {
            if (count($items) < 2) {
                continue;
            }

            $duplicateOccurrences += count($items) - 1;
            $payloadFingerprints = array_unique(array_map(
                static fn (array $item): string => json_encode($item['payload'], JSON_THROW_ON_ERROR),
                $items,
            ));
            $classification = count($payloadFingerprints) === 1 ? 'exact' : 'conflicting';
            $classification === 'exact' ? $exactDuplicateIds++ : $conflictingDuplicateIds++;
            $duplicates[] = [
                'external_id' => (string) $externalId,
                'occurrence_count' => count($items),
                'classification' => $classification,
                'page_starts' => array_column($items, 'requested_start'),
            ];
        }

        $overlaps = [];
        $pagesWithOverlap = 0;
        $totalOverlappingOccurrences = 0;
        $starts = array_keys($pageIds);
        for ($index = 1; $index < count($starts); $index++) {
            $leftStart = $starts[$index - 1];
            $rightStart = $starts[$index];
            $intersection = array_values(array_intersect($pageIds[$leftStart], $pageIds[$rightStart]));
            $overlapCount = count($intersection);
            if ($overlapCount > 0) {
                $pagesWithOverlap++;
                $totalOverlappingOccurrences += $overlapCount;
                $firstAnomalousStart ??= $rightStart;
            }
            $overlaps[] = [
                'left_start' => $leftStart,
                'right_start' => $rightStart,
                'count' => $overlapCount,
            ];
        }

        $uniqueExternalIds = count($occurrences);

        return [
            'count' => $initialCount,
            'count_all' => $initialCountAll,
            'limit' => $requestedLimit,
            'order' => $initialOrder,
            'expected_pages' => $initialCount === null ? null : (int) ceil($initialCount / $requestedLimit),
            'pages' => $pages,
            'successful_pages' => count($pages),
            'raw_fetched' => $rawFetched,
            'unique_external_ids' => $uniqueExternalIds,
            'duplicate_occurrences' => $duplicateOccurrences,
            'duplicate_external_id_count' => count($duplicates),
            'exact_duplicate_ids' => $exactDuplicateIds,
            'conflicting_duplicate_ids' => $conflictingDuplicateIds,
            'duplicate_examples' => array_slice($duplicates, 0, 10),
            'overlaps' => $overlaps,
            'pages_with_overlap' => $pagesWithOverlap,
            'total_overlapping_occurrences' => $totalOverlappingOccurrences,
            'missing_expected_amount' => $initialCount === null ? null : max(0, $initialCount - $rawFetched),
            'metadata_changes' => $metadataChanges,
            'duplicated_page_pairs' => $duplicatedPagePairs,
            'first_anomalous_start' => $firstAnomalousStart,
            'request_failure' => $requestFailure,
        ];
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /** @return array{data:array<int,mixed>,total:int,count_all:int,next_start:int,response_limit:int,order:array<string,mixed>|null} */
    private function fetchPage(int $start): array
    {
        $payload = $this->requestPage($start);

        return [
            'data' => $payload['data'],
            'total' => $payload['total'],
            'count_all' => $payload['count_all'],
            'next_start' => $payload['response_start'] + $payload['response_limit'],
            'response_limit' => $payload['response_limit'],
            'order' => $payload['order'],
        ];
    }

    /**
     * @return array{data:array<int,mixed>,total:int,count_all:int,response_start:int,response_limit:int,order:array<string,mixed>|null}
     */
    private function requestPage(int $start, bool $enforceRequestedStart = true): array
    {
        if (trim($this->baseUrl) === '') {
            throw new DisbunReferenceSyncException('SHARED_API_BASE_URL belum diisi.');
        }

        $request = Http::acceptJson()
            ->withHeaders(['User-Agent' => $this->userAgent])
            ->retry(
                $this->retryAttempts(),
                fn (int $attempt, Throwable $exception): int => $this->retryDelayMs($attempt, $exception),
                fn (Throwable $exception): bool => $this->shouldRetry($exception),
            )
            ->timeout($this->timeout);
        if ($this->token !== '') {
            $request = $request->withToken($this->token);
        }

        $limit = $this->limit();
        try {
            $response = $request->get("{$this->baseUrl}/api/kelompok-tani", [
                'start' => $start,
                'limit' => $limit,
            ]);
        } catch (Throwable $e) {
            if ($e instanceof RequestException && $e->response !== null) {
                throw new DisbunReferenceSyncException("API kelompok tani menolak request pada start={$start} (HTTP {$e->response->status()}).", 0, $e);
            }

            $detail = trim($e->getMessage()) ?: get_class($e);
            throw new DisbunReferenceSyncException("API kelompok tani tidak dapat dihubungi pada start={$start}: {$detail}", 0, $e);
        }

        if ($response->failed()) {
            Log::warning('Gagal ambil referensi kelompok tani dari Disbun API', [
                'status' => $response->status(),
                'start' => $start,
                'limit' => $limit,
            ]);

            throw new DisbunReferenceSyncException("API kelompok tani gagal pada start={$start} (HTTP {$response->status()}).");
        }

        $root = $response->json();
        $result = is_array($root) ? ($root['data']['result'] ?? null) : null;
        $status = is_array($root) ? ($root['status'] ?? null) : null;
        $ecode = is_array($root) ? ($root['ecode'] ?? null) : null;

        if ($status !== true || ! is_numeric($ecode) || (int) $ecode !== 0 || ! is_array($result)) {
            throw new DisbunReferenceSyncException("Response kelompok tani malformed pada start={$start}: status/ecode/result tidak valid.");
        }

        $data = $result['data'] ?? null;
        $total = $result['count'] ?? null;
        $countAll = $result['count_all'] ?? null;
        $reportedStart = $result['start'] ?? null;
        $reportedLimit = $result['limit'] ?? null;

        if (! is_array($data) || ! array_is_list($data)
            || ! is_numeric($total) || (int) $total < 0
            || ! is_numeric($countAll) || (int) $countAll < (int) $total
            || ! is_numeric($reportedStart)
            || ! is_numeric($reportedLimit) || (int) $reportedLimit <= 0) {
            throw new DisbunReferenceSyncException("Response kelompok tani malformed pada start={$start}: metadata pagination tidak valid.");
        }

        if ($enforceRequestedStart && (int) $reportedStart !== $start) {
            throw new DisbunReferenceSyncException("Response kelompok tani malformed pada start={$start}: response start tidak sesuai request.");
        }

        return [
            'data' => $data,
            'total' => (int) $total,
            'count_all' => (int) $countAll,
            'response_start' => (int) $reportedStart,
            'response_limit' => (int) $reportedLimit,
            'order' => is_array($root['data']['order'] ?? null) ? $root['data']['order'] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function duplicateComparisonPayload(array $row): array
    {
        $payload = [];
        foreach (self::DUPLICATE_COMPARISON_FIELDS as $field) {
            $payload[$field] = $row[$field] ?? null;
        }

        return $payload;
    }

    /** @return array<string,mixed>|null */
    private function normalize(array $row): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $nama = $this->nullableString($row['nama_kelompok'] ?? null);

        if ($id <= 0 || $nama === null) {
            return null;
        }

        $jenisKomoditi = $this->nullableString($row['jenis_komoditi'] ?? null);

        return [
            'id' => $id,
            'kode' => $this->nullableString($row['kode_kelompok'] ?? null),
            'kode_kelompok' => $this->nullableString($row['kode_kelompok'] ?? null),
            'nama' => $nama,
            'ketua' => $this->nullableString($row['ketua'] ?? null),
            'kabupaten' => $this->nullableString($row['kabupaten'] ?? null),
            'kecamatan' => $this->nullableString($row['kecamatan'] ?? null),
            'desa' => $this->nullableString($row['kelurahan'] ?? null),
            'kelurahan' => $this->nullableString($row['kelurahan'] ?? null),
            'kode_kabupaten' => $this->nullableString($row['kode_kabupaten'] ?? null),
            'kode_kecamatan' => $this->nullableString($row['kode_kecamatan'] ?? null),
            'kode_desa' => $this->nullableString($row['kode_desa'] ?? null),
            'jenis_komoditi' => $jenisKomoditi,
            'latitude' => $this->coordinate($row['latitude'] ?? null),
            'longitude' => $this->coordinate($row['longitude'] ?? null),
            'status' => $this->nullableString($row['status'] ?? null),
            'deleted_at' => $this->nullableString($row['deleted_at'] ?? null),
            'source_updated_at' => $this->nullableString($row['updated_at'] ?? null),
            'is_active' => $this->sourceIsActive($row),
            // These values are metadata only. They are never used as a
            // foreign-key match for ref_komoditas.
            '_external_commodity_id' => isset($row['id_komoditi']) ? (string) $row['id_komoditi'] : null,
            '_external_commodity_code' => null,
            '_external_commodity_name' => $jenisKomoditi,
        ];
    }

    private function invalidReason(array $row): string
    {
        if ((int) ($row['id'] ?? 0) <= 0) {
            return 'missing_external_id';
        }

        if ($this->nullableString($row['nama_kelompok'] ?? null) === null) {
            return 'missing_name';
        }

        return 'invalid_payload';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function coordinate(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    private function sourceIsActive(array $row): bool
    {
        if (array_key_exists('is_active', $row)) {
            return filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $row['is_active'];
        }

        if ($this->nullableString($row['deleted_at'] ?? null) !== null) {
            return false;
        }

        $status = $this->canonical($row['status'] ?? null);
        if (in_array($status, ['0', 'false', 'inactive', 'nonaktif', 'tidak aktif', 'deleted'], true)) {
            return false;
        }

        return true;
    }

    private function canonical(mixed $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private function limit(): int
    {
        return max(1, min($this->pageSize, 100));
    }

    private function maxPages(): int
    {
        return max(200, $this->maxPages);
    }

    private function sourceExhaustionWarningRatio(): float
    {
        return max(0.5, min($this->sourceExhaustionWarningRatio, 0.999));
    }

    private function delayBeforeNextPage(): void
    {
        $delayMs = max(0, min($this->pageDelayMs, 10000));
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    private function retryAttempts(): int
    {
        return max(1, min($this->rateLimitRetries + 1, 6));
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if (! $exception instanceof RequestException || $exception->response === null) {
            return true;
        }

        return $exception->response->status() === 429 || $exception->response->serverError();
    }

    private function retryDelayMs(int $attempt, Throwable $exception): int
    {
        if ($exception instanceof RequestException && $exception->response?->status() === 429) {
            $retryAfter = $exception->response->header('Retry-After');
            if (is_numeric($retryAfter)) {
                return max(0, min((int) $retryAfter * 1000, 120000));
            }

            if (is_string($retryAfter) && strtotime($retryAfter) !== false) {
                return max(0, min((strtotime($retryAfter) - time()) * 1000, 120000));
            }

            return max(0, min($this->rateLimitBackoffMs, 120000));
        }

        return min(2000, max(200, $attempt * 200));
    }

}
