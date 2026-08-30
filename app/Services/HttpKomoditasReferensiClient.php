<?php

namespace App\Services;

use App\Contracts\KomoditasReferensiClient;
use App\Exceptions\DisbunReferenceSyncException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads the actual Disbun commodity contract:
 * GET /api/komoditas?start=0&limit=50
 *
 * The response is a root array. It is deliberately not parsed as a Laravel
 * resource or paginator envelope because this endpoint has a different
 * contract from kelompok-tani.
 */
class HttpKomoditasReferensiClient implements KomoditasReferensiClient
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $token = '',
        private readonly int $timeout = 30,
        private readonly int $pageSize = 50,
        private readonly int $maxPages = 250,
        private readonly string $userAgent = 'SIPAKARBUN/1.0',
    ) {}

    public function all(): array
    {
        try {
            return $this->fetchAllWithReport()->rows;
        } catch (Throwable $e) {
            Log::error('Exception saat panggil API Disbun komoditas', ['message' => $e->getMessage()]);

            return [];
        }
    }

    public function fetchAllWithReport(?callable $progress = null): ReferenceFetchResult
    {
        $rows = [];
        $seen = [];
        $fetched = 0;
        $valid = 0;
        $quarantined = 0;
        $reasons = [];
        $limit = $this->limit();
        $start = 0;
        $pages = 0;

        while (true) {
            if ($pages >= $this->maxPages()) {
                throw new DisbunReferenceSyncException('Pagination komoditas melewati batas maksimum '.$this->maxPages().' halaman.');
            }

            $payload = $this->fetchPage($start, $limit);
            $pageCount = count($payload['data']);
            $pages++;
            $progress?->__invoke($start, $pageCount);

            foreach ($payload['data'] as $raw) {
                $fetched++;
                if (! is_array($raw)) {
                    $quarantined++;
                    $reasons['invalid_payload'] = ($reasons['invalid_payload'] ?? 0) + 1;
                    continue;
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

            if ($pageCount < $limit) {
                break;
            }

            $start += $limit;
        }

        return new ReferenceFetchResult(
            rows: $rows,
            fetched: $fetched,
            valid: $valid,
            quarantined: $quarantined,
            pages: $pages,
            quarantineReasons: $reasons,
            total: $fetched,
            countAll: $fetched,
        );
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

    /** @return array{data:array<int,mixed>} */
    private function fetchPage(int $start, int $limit): array
    {
        if (trim($this->baseUrl) === '') {
            throw new DisbunReferenceSyncException('SHARED_API_BASE_URL belum diisi.');
        }

        $request = Http::acceptJson()
            ->withHeaders(['User-Agent' => $this->userAgent])
            ->retry(2, 200)
            ->timeout($this->timeout);
        if ($this->token !== '') {
            $request = $request->withToken($this->token);
        }

        try {
            $response = $request->get("{$this->baseUrl}/api/komoditas", [
                'start' => $start,
                'limit' => $limit,
            ]);
        } catch (Throwable $e) {
            if ($e instanceof RequestException && $e->response !== null) {
                throw new DisbunReferenceSyncException("API komoditas menolak request pada start={$start} (HTTP {$e->response->status()}).", 0, $e);
            }

            $detail = trim($e->getMessage()) ?: get_class($e);
            throw new DisbunReferenceSyncException("API komoditas tidak dapat dihubungi pada start={$start}: {$detail}", 0, $e);
        }

        if ($response->failed()) {
            Log::warning('Gagal ambil referensi komoditas dari Disbun API', [
                'status' => $response->status(),
                'start' => $start,
                'limit' => $limit,
            ]);

            throw new DisbunReferenceSyncException("API komoditas gagal pada start={$start} (HTTP {$response->status()}).");
        }

        $root = $response->json();
        if (! is_array($root) || ! array_is_list($root)) {
            throw new DisbunReferenceSyncException("Response komoditas bukan root array pada start={$start}.");
        }

        return ['data' => $root];
    }

    /** @return array{id:int,kode:?string,nama:string,nama_latin:?string,is_active:bool}|null */
    private function normalize(array $row): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $nama = $this->nullableString($row['nama'] ?? null);

        if ($id <= 0 || $nama === null) {
            return null;
        }

        return [
            'id' => $id,
            'kode' => $this->nullableString($row['kode'] ?? null),
            'nama' => $nama,
            'nama_latin' => $this->nullableString($row['nama_latin'] ?? null),
            'is_active' => $this->toBoolean($row['is_active'] ?? true),
        ];
    }

    private function invalidReason(array $row): string
    {
        if ((int) ($row['id'] ?? 0) <= 0) {
            return 'missing_external_id';
        }

        if ($this->nullableString($row['nama'] ?? null) === null) {
            return 'missing_name';
        }

        return 'invalid_payload';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    private function limit(): int
    {
        return max(1, min($this->pageSize, 100));
    }

    private function maxPages(): int
    {
        return max(200, $this->maxPages);
    }
}
