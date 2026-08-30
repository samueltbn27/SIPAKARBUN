<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Exceptions\DisbunReferenceSyncException;
use App\Services\HttpKelompokTaniReferensiClient;
use App\Services\HttpKomoditasReferensiClient;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SharedReferensiClientResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.shared_referensi.base_url' => '']);
    }

    public function test_komoditas_resolves_mock_saat_base_url_kosong(): void
    {
        $this->assertInstanceOf(MockKomoditasReferensiClient::class, app(KomoditasReferensiClient::class));
    }

    public function test_kelompok_tani_resolves_mock_saat_base_url_kosong(): void
    {
        $this->assertInstanceOf(MockKelompokTaniReferensiClient::class, app(KelompokTaniReferensiClient::class));
    }

    public function test_komoditas_membaca_root_array_dan_menerima_kode_null(): void
    {
        Http::fake([
            '*/api/komoditas*' => Http::response([
                ['id' => 38, 'kode' => null, 'nama' => 'Kopi Robusta', 'nama_latin' => null, 'is_active' => true],
                ['id' => 14, 'kode' => null, 'nama' => 'Kecambah Kelapa Sawit', 'nama_latin' => null, 'is_active' => true],
            ]),
        ]);

        $client = new HttpKomoditasReferensiClient('http://disbun.test', pageSize: 50);
        $result = $client->fetchAllWithReport();

        $this->assertSame(2, $result->fetched);
        $this->assertSame(2, $result->valid);
        $this->assertNull($result->rows[0]['kode']);
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=0') && str_contains($request->url(), 'limit=50'));
        Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('User-Agent', 'SIPAKARBUN/1.0'));
    }

    public function test_kelompok_tani_membaca_nested_contract_dan_semua_halaman(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $count = match ($start) {
                0 => 50,
                50 => 50,
                100 => 15,
                default => 0,
            };
            $rows = [];

            if ($count > 0) {
                foreach (range($start + 1, $start + $count) as $id) {
                    $rows[] = [
                        'id' => $id,
                        'kode_kelompok' => 'KT-'.$id,
                        'nama_kelompok' => 'Poktan '.$id,
                        'jenis_komoditi' => 'Kopi Robusta',
                        'kabupaten' => 'Kabupaten Bandung',
                        'kecamatan' => 'Kecamatan '.$id,
                        'kelurahan' => 'Desa '.$id,
                        'latitude' => '-7.0',
                        'longitude' => '107.5',
                        'status' => 'aktif',
                    ];
                }
            }

            return Http::response($this->nestedPoktanPayload($rows, $start, 115, 50));
        });

        $client = new HttpKelompokTaniReferensiClient('http://disbun.test', pageSize: 50, pageDelayMs: 0);
        $result = $client->fetchAllWithReport();

        $this->assertSame(115, $result->fetched);
        $this->assertSame(115, $result->valid);
        $this->assertSame(115, $result->total);
        $this->assertSame(115, $result->countAll);
        $this->assertSame(4, $result->pages);
        $this->assertSame('Poktan 115', $result->rows[114]['nama']);
        $this->assertSame(-7.0, $result->rows[0]['latitude']);
        Http::assertSentCount(4);
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=50') && str_contains($request->url(), 'limit=50'));
    }

    public function test_komoditas_melanjutkan_pagination_jika_halaman_memenuhi_limit(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $pageCount = $start === 0 ? 2 : 1;
            $rows = [];

            foreach (range($start + 1, $start + $pageCount) as $id) {
                $rows[] = ['id' => $id, 'nama' => 'Komoditas '.$id];
            }

            return Http::response($rows);
        });

        $result = (new HttpKomoditasReferensiClient(
            'http://disbun.test',
            pageSize: 2,
            maxPages: 250,
        ))->fetchAllWithReport();

        $this->assertSame(3, $result->fetched);
        $this->assertSame(2, $result->pages);
        $this->assertCount(3, $result->rows);
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=2') && str_contains($request->url(), 'limit=2'));
    }

    public function test_kelompok_tani_menolak_envelope_lama_yang_tidak_sesuai_kontrak(): void
    {
        Http::fake(['*/api/kelompok-tani*' => Http::response(['data' => []])]);

        $this->assertSame([], (new HttpKelompokTaniReferensiClient('http://disbun.test'))->all());
    }

    public function test_pagination_gagal_mengembalikan_kosong_dan_tidak_data_parsial(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);

            return $start === 0
                ? Http::response($this->nestedPoktanPayload([['id' => 1, 'nama_kelompok' => 'Halaman Satu']], 0, 51, 50))
                : Http::response([], 503);
        });

        $client = new HttpKelompokTaniReferensiClient('http://disbun.test');
        $this->assertSame([], $client->all());
        $this->assertNull($client->find(1));
    }

    public function test_kelompok_tani_mendeduplikasi_exact_external_id(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start === 0 ? [
                ['id' => 7, 'nama_kelompok' => 'Record Sama'],
                ['id' => 7, 'nama_kelompok' => 'Record Sama'],
            ] : [];

            return Http::response($this->nestedPoktanPayload($rows, $start, 2, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient('http://disbun.test'))->fetchAllWithReport();

        $this->assertSame(2, $result->fetched);
        $this->assertCount(1, $result->rows);
        $this->assertSame('Record Sama', $result->rows[0]['nama']);
    }

    public function test_audit_mengklasifikasikan_exact_conflicting_dan_overlap(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $rows = match ($start) {
                0 => [
                    ['id' => 1, 'kode_kelompok' => 'KT-1', 'nama_kelompok' => 'Poktan A'],
                    ['id' => 2, 'kode_kelompok' => 'KT-2', 'nama_kelompok' => 'Poktan B'],
                ],
                2 => [
                    ['id' => 2, 'kode_kelompok' => 'KT-2', 'nama_kelompok' => 'Poktan B'],
                    ['id' => 3, 'kode_kelompok' => 'KT-3', 'nama_kelompok' => 'Poktan C'],
                ],
                default => [
                    ['id' => 3, 'kode_kelompok' => 'KT-3-BARU', 'nama_kelompok' => 'Poktan C Berubah'],
                    ['id' => 4, 'kode_kelompok' => 'KT-4', 'nama_kelompok' => 'Poktan D'],
                ],
            };

            return Http::response($this->nestedPoktanPayload($rows, $start, 6, 2));
        });

        $audit = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 2,
            pageDelayMs: 0,
        ))->auditPagination();

        $this->assertSame(6, $audit['raw_fetched']);
        $this->assertSame(4, $audit['unique_external_ids']);
        $this->assertSame(2, $audit['duplicate_occurrences']);
        $this->assertSame(2, $audit['duplicate_external_id_count']);
        $this->assertSame(1, $audit['exact_duplicate_ids']);
        $this->assertSame(1, $audit['conflicting_duplicate_ids']);
        $this->assertSame(2, $audit['pages_with_overlap']);
        $this->assertSame(2, $audit['total_overlapping_occurrences']);
        $this->assertSame(2, $audit['first_anomalous_start']);
        $this->assertSame([
            ['external_id' => '2', 'occurrence_count' => 2, 'classification' => 'exact', 'page_starts' => [0, 2]],
            ['external_id' => '3', 'occurrence_count' => 2, 'classification' => 'conflicting', 'page_starts' => [2, 4]],
        ], $audit['duplicate_examples']);
    }

    public function test_audit_melanjutkan_setelah_halaman_pendek_dan_mencatat_metadata_berubah(): void
    {
        $requestedStarts = [];
        Http::fake(function (HttpRequest $request) use (&$requestedStarts) {
            $start = (int) ($request->data()['start'] ?? 0);
            $requestedStarts[] = $start;
            $rows = match ($start) {
                0 => [
                    ['id' => 1, 'nama_kelompok' => 'Poktan 1'],
                    ['id' => 2, 'nama_kelompok' => 'Poktan 2'],
                ],
                2 => [['id' => 3, 'nama_kelompok' => 'Poktan 3']],
                default => [['id' => 4, 'nama_kelompok' => 'Poktan 4']],
            };
            $count = $start === 0 ? 5 : 6;

            return Http::response($this->nestedPoktanPayload($rows, $start, $count, 2));
        });

        $audit = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 2,
            pageDelayMs: 0,
        ))->auditPagination();

        $this->assertSame([0, 2, 4], $requestedStarts);
        $this->assertSame(3, $audit['successful_pages']);
        $this->assertSame(4, $audit['raw_fetched']);
        $this->assertSame(1, $audit['missing_expected_amount']);
        $this->assertSame(2, $audit['first_anomalous_start']);
        $this->assertCount(2, $audit['metadata_changes']);
        $this->assertNull($audit['request_failure']);
    }

    public function test_audit_mendeteksi_halaman_yang_mengulang_urutan_id_sebelumnya(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);

            return Http::response($this->nestedPoktanPayload([
                ['id' => 1, 'nama_kelompok' => 'Poktan 1'],
                ['id' => 2, 'nama_kelompok' => 'Poktan 2'],
            ], $start, 4, 2));
        });

        $audit = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 2,
            pageDelayMs: 0,
        ))->auditPagination();

        $this->assertSame([['left_start' => 0, 'right_start' => 2]], $audit['duplicated_page_pairs']);
        $this->assertSame(2, $audit['total_overlapping_occurrences']);
        $this->assertSame(2, $audit['first_anomalous_start']);
    }

    public function test_komoditas_mengarantina_record_tanpa_id_atau_nama(): void
    {
        Http::fake([
            '*/api/komoditas*' => Http::response([
                ['id' => 1, 'nama' => 'Valid'],
                ['id' => 0, 'nama' => 'Tanpa ID'],
                ['id' => 2, 'nama' => null],
            ]),
        ]);

        $result = (new HttpKomoditasReferensiClient('http://disbun.test'))->fetchAllWithReport();

        $this->assertSame(3, $result->fetched);
        $this->assertSame(1, $result->valid);
        $this->assertSame(2, $result->quarantined);
        $this->assertSame(['missing_external_id' => 1, 'missing_name' => 1], $result->quarantineReasons);
    }

    public function test_http_client_down_mengembalikan_array_kosong(): void
    {
        Http::fake(['*/api/kelompok-tani*' => Http::response([], 503)]);

        $client = new HttpKelompokTaniReferensiClient('http://disbun.test');

        $this->assertSame([], $client->all());
        $this->assertNull($client->find(1));
    }

    public function test_kelompok_tani_menunggu_dan_mencoba_ulang_saat_rate_limited(): void
    {
        $attempt = 0;
        Http::fake(function (HttpRequest $request) use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                return Http::response([], 429, ['Retry-After' => '0']);
            }

            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start === 0
                ? [['id' => 1, 'nama_kelompok' => 'Poktan Setelah Retry']]
                : [];

            return Http::response($this->nestedPoktanPayload($rows, $start, 1, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageDelayMs: 0,
            rateLimitRetries: 1,
            rateLimitBackoffMs: 0,
        ))->fetchAllWithReport();

        $this->assertSame(1, $result->fetched);
        $this->assertSame('Poktan Setelah Retry', $result->rows[0]['nama']);
        Http::assertSentCount(3);
    }

    public function test_kelompok_tani_menerima_source_exhaustion_terverifikasi_dengan_warning(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $count = match ($start) {
                0, 50 => 50,
                100 => 10,
                default => 0,
            };
            $rows = [];
            if ($count > 0) {
                foreach (range($start + 1, $start + $count) as $id) {
                    $rows[] = ['id' => $id, 'nama_kelompok' => 'Poktan '.$id];
                }
            }

            return Http::response($this->nestedPoktanPayload($rows, $start, 115, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            pageDelayMs: 0,
        ))->fetchAllWithReport();

        $this->assertSame(110, $result->fetched);
        $this->assertSame(110, $result->uniqueExternalIds);
        $this->assertSame(4, $result->pages);
        $this->assertSame(100, $result->terminalShortPageStart);
        $this->assertSame(150, $result->sourceExhaustedAt);
        $this->assertSame(5, $result->metadataGap);
        $this->assertTrue($result->sourceWarning);
    }

    public function test_kelompok_tani_menolak_empty_page_yang_terlalu_awal(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start < 100
                ? array_map(fn (int $id): array => ['id' => $id, 'nama_kelompok' => 'Poktan '.$id], range($start + 1, $start + 50))
                : [];

            return Http::response($this->nestedPoktanPayload($rows, $start, 1000, 50));
        });

        $this->expectException(DisbunReferenceSyncException::class);
        $this->expectExceptionMessage('Halaman kosong kelompok tani muncul terlalu awal pada start=100.');

        (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            pageDelayMs: 0,
        ))->fetchAllWithReport();
    }

    public function test_completion_ratio_hanya_warning_setelah_short_page_dan_empty_confirmation(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start === 0
                ? array_map(fn (int $id): array => ['id' => $id, 'nama_kelompok' => 'Poktan '.$id], range(1, 10))
                : [];

            return Http::response($this->nestedPoktanPayload($rows, $start, 1000, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            pageDelayMs: 0,
            sourceExhaustionWarningRatio: 0.90,
        ))->fetchAllWithReport();

        $this->assertSame(10, $result->fetched);
        $this->assertSame(0, $result->terminalShortPageStart);
        $this->assertSame(50, $result->sourceExhaustedAt);
        $this->assertSame(0.01, $result->sourceCompletionRatio);
        $this->assertContains('metadata_count_mismatch', $result->warningReasons);
        $this->assertContains('low_source_completion_ratio', $result->warningReasons);
    }

    public function test_exact_duplicate_diizinkan_dan_direkonsiliasi(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start === 0 ? [
                ['id' => 10, 'kode_kelompok' => 'KT-10', 'nama_kelompok' => 'Poktan A'],
                ['id' => 10, 'kode_kelompok' => 'KT-10', 'nama_kelompok' => 'Poktan A'],
            ] : [];

            return Http::response($this->nestedPoktanPayload($rows, $start, 2, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            pageDelayMs: 0,
        ))->fetchAllWithReport();

        $this->assertSame(2, $result->fetched);
        $this->assertSame(1, $result->uniqueExternalIds);
        $this->assertSame(1, $result->duplicateOccurrences);
        $this->assertSame(1, $result->exactDuplicateOccurrences);
        $this->assertSame(0, $result->conflictingDuplicateIds);
        $this->assertCount(1, $result->rows);
    }

    public function test_conflicting_duplicate_gagal_secara_fail_closed(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start === 0 ? [
                ['id' => 10, 'kode_kelompok' => 'KT-10', 'nama_kelompok' => 'Poktan A'],
                ['id' => 10, 'kode_kelompok' => 'KT-10-B', 'nama_kelompok' => 'Poktan B'],
            ] : [];

            return Http::response($this->nestedPoktanPayload($rows, $start, 2, 50));
        });

        $this->expectException(DisbunReferenceSyncException::class);
        $this->expectExceptionMessage('Conflicting duplicate external ID kelompok tani: 10.');

        (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            pageDelayMs: 0,
        ))->fetchAllWithReport();
    }

    public function test_kebijakan_source_exhaustion_merekonsiliasi_snapshot_live_5927_ke_5650_unique(): void
    {
        Http::fake(function (HttpRequest $request) {
            $start = (int) ($request->data()['start'] ?? 0);
            $pageCount = match (true) {
                $start < 5900 => 50,
                $start === 5900 => 27,
                default => 0,
            };
            $rows = [];

            for ($offset = 0; $offset < $pageCount; $offset++) {
                $rawOrdinal = $start + $offset + 1;
                $externalId = $rawOrdinal <= 5650 ? $rawOrdinal : $rawOrdinal - 5650;
                $rows[] = [
                    'id' => $externalId,
                    'kode_kelompok' => 'KT-'.$externalId,
                    'nama_kelompok' => 'Poktan '.$externalId,
                    'kabupaten' => 'Kabupaten Bandung',
                    'updated_at' => '2026-08-01 00:00:00',
                ];
            }

            return Http::response($this->nestedPoktanPayload($rows, $start, 6015, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            maxPages: 250,
            pageDelayMs: 0,
        ))->fetchAllWithReport();

        $this->assertSame(5927, $result->fetched);
        $this->assertSame(5650, $result->uniqueExternalIds);
        $this->assertSame(277, $result->duplicateOccurrences);
        $this->assertSame(277, $result->exactDuplicateOccurrences);
        $this->assertSame(0, $result->conflictingDuplicateIds);
        $this->assertCount(5650, $result->rows);
        $this->assertSame(5900, $result->terminalShortPageStart);
        $this->assertSame(5950, $result->sourceExhaustedAt);
        $this->assertEqualsWithDelta(5927 / 6015, $result->sourceCompletionRatio, 0.000001);
        $this->assertSame(88, $result->metadataGap);
        $this->assertTrue($result->sourceWarning);
    }

    public function test_pagination_6015_meminta_121_halaman_dan_satu_konfirmasi_kosong(): void
    {
        $requestedStarts = [];
        Http::fake(function (HttpRequest $request) use (&$requestedStarts) {
            $start = (int) ($request->data()['start'] ?? 0);
            $requestedStarts[] = $start;
            $pageCount = max(0, min(50, 6015 - $start));
            $rows = [];

            if ($pageCount > 0) {
                foreach (range($start + 1, $start + $pageCount) as $id) {
                    $rows[] = ['id' => $id, 'nama_kelompok' => 'Poktan '.$id];
                }
            }

            return Http::response($this->nestedPoktanPayload($rows, $start, 6015, 50));
        });

        $result = (new HttpKelompokTaniReferensiClient(
            'http://disbun.test',
            pageSize: 50,
            maxPages: 250,
            pageDelayMs: 0,
        ))->fetchAllWithReport();

        $this->assertSame(6015, $result->fetched);
        $this->assertSame(122, $result->pages);
        $this->assertCount(122, $requestedStarts);
        $this->assertSame(0, $requestedStarts[0]);
        $this->assertSame(6000, $requestedStarts[120]);
        $this->assertSame(6050, $requestedStarts[121]);
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function nestedPoktanPayload(array $rows, int $start, int $count, int $limit): array
    {
        return [
            'status' => true,
            'ecode' => 0,
            'message' => 'Success',
            'data' => [
                'filter' => [],
                'order' => ['by' => 'kelompok_tani.created_at', 'asc' => 1],
                'result' => [
                    'count' => $count,
                    'count_all' => $count,
                    'start' => $start,
                    'limit' => $limit,
                    'data' => $rows,
                ],
            ],
        ];
    }
}
