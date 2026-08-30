<?php

namespace Tests\Feature;

use App\Exceptions\DisbunReferenceSyncException;
use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Services\DisbunReferenceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Tests\TestCase;

class DisbunReferenceSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.shared_referensi.base_url' => 'http://disbun.test',
            'services.shared_referensi.page_size' => 50,
            'services.shared_referensi.page_delay_ms' => 0,
            'services.shared_referensi.rate_limit_backoff_ms' => 0,
        ]);
    }

    public function test_sync_mengambil_semua_halaman_dan_idempotent(): void
    {
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/api/komoditas')) {
                return Http::response([
                    ['id' => 38, 'kode' => null, 'nama' => 'Tebu', 'nama_latin' => null, 'is_active' => true],
                    ['id' => 40, 'kode' => null, 'nama' => 'Kopi Robusta', 'nama_latin' => null, 'is_active' => true],
                    ['id' => 50, 'kode' => null, 'nama' => 'Vanili', 'nama_latin' => null, 'is_active' => true],
                    ['id' => 70, 'kode' => null, 'nama' => 'Kelapa', 'nama_latin' => null, 'is_active' => true],
                ]);
            }

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
                        'jenis_komoditi' => match ($id) {
                            1 => 'Kopi Robusta',
                            2 => 'Panili',
                            3 => 'Kelapa Dalam',
                            4 => 'Commodity Tidak Dikenal',
                            default => null,
                        },
                        // Deliberately incompatible with the commodity endpoint
                        // for id 38: 38 is Tebu there, not Kopi Robusta.
                        'id_komoditi' => match ($id) {
                            1 => 38,
                            2 => 999,
                            3 => 13,
                            4 => 888,
                            default => null,
                        },
                        'kabupaten' => 'Kabupaten Bandung',
                        'kecamatan' => 'Pangalengan',
                        'kelurahan' => 'Margamulya',
                        'kode_kabupaten' => '32.04',
                        'kode_kecamatan' => '32.04.01',
                        'kode_desa' => '32.04.01.2001',
                        'latitude' => '-7.025',
                        'longitude' => '107.519',
                        'status' => 'aktif',
                    ];
                }
            }

            return Http::response($this->nestedPayload($rows, $start, 115, 50));
        });

        $report = app(DisbunReferenceSyncService::class)->syncAllReferences();

        $this->assertSame(4, $report['komoditas']['fetched']);
        $this->assertSame(115, $report['kelompok_tani']['fetched']);
        $this->assertSame(115, $report['kelompok_tani']['valid']);
        $this->assertSame(4, $report['kelompok_tani']['pages']);
        $this->assertSame(115, RefKelompokTani::count());
        $this->assertSame(4, RefKomoditas::where('source', RefKomoditas::SOURCE_DISBUN)->count());
        $this->assertSame('mapped', RefKelompokTani::where('disbun_record_id', '1')->value('commodity_mapping_status'));
        $kopiRef = RefKomoditas::find(RefKelompokTani::where('disbun_record_id', '1')->value('commodity_ref_id'));
        $vaniliRef = RefKomoditas::find(RefKelompokTani::where('disbun_record_id', '2')->value('commodity_ref_id'));
        $kelapaRef = RefKomoditas::find(RefKelompokTani::where('disbun_record_id', '3')->value('commodity_ref_id'));
        $this->assertSame('Kopi Robusta', $kopiRef?->nama);
        $this->assertSame('Vanili', $vaniliRef?->nama);
        $this->assertSame('Kelapa', $kelapaRef?->nama);
        $this->assertSame(1, $report['kelompok_tani']['unresolved_commodity']);
        $this->assertDatabaseHas('ref_kelompok_tani', [
            'disbun_record_id' => '4',
            'jenis_komoditi' => 'Commodity Tidak Dikenal',
            'commodity_ref_id' => null,
            'commodity_mapping_status' => 'unresolved',
        ]);
        $this->assertSame('32.04.01.2001', RefKelompokTani::where('disbun_record_id', '1')->value('kode_desa'));
        $this->assertSame('-7.0250000', RefKelompokTani::where('disbun_record_id', '1')->value('latitude'));

        $reportAgain = app(DisbunReferenceSyncService::class)->syncAllReferences();
        $this->assertSame(115, RefKelompokTani::count());
        $this->assertSame(4, RefKomoditas::where('source', RefKomoditas::SOURCE_DISBUN)->count());
        $this->assertSame(115, $reportAgain['kelompok_tani']['local']);
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=0') && str_contains($request->url(), 'limit=50'));
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=50') && str_contains($request->url(), 'limit=50'));
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=100') && str_contains($request->url(), 'limit=50'));
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'start=150') && str_contains($request->url(), 'limit=50'));
    }

    public function test_page_gagal_tidak_menyimpan_dataset_parsial_dan_mempertahankan_last_good(): void
    {
        RefKomoditas::create(['kode' => 'OLD', 'nama' => 'Last Good', 'source' => RefKomoditas::SOURCE_DISBUN, 'disbun_record_id' => 700, 'is_verified' => true, 'sync_status' => RefKomoditas::SYNC_SYNCED]);
        RefKelompokTani::create(['disbun_record_id' => '700', 'nama' => 'Last Good Poktan', 'is_verified' => true, 'sync_status' => RefKelompokTani::SYNC_SYNCED]);
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/api/komoditas')) {
                return Http::response([['id' => 1, 'kode' => null, 'nama' => 'Kopi']]);
            }

            return (int) ($request->data()['start'] ?? 0) === 0
                ? Http::response($this->nestedPayload(array_fill(0, 50, ['id' => 1, 'nama_kelompok' => 'Poktan']), 0, 100, 50))
                : Http::response([], 503);
        });

        $this->expectException(DisbunReferenceSyncException::class);
        try {
            app(DisbunReferenceSyncService::class)->syncAllReferences();
        } finally {
            $this->assertSame(1, RefKelompokTani::count());
            $this->assertSame(1, RefKomoditas::where('source', RefKomoditas::SOURCE_DISBUN)->count());
        }
    }

    public function test_source_exhaustion_menyinkronkan_unique_rows_dengan_source_warning(): void
    {
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/api/komoditas')) {
                return Http::response([['id' => 40, 'kode' => null, 'nama' => 'Kopi Robusta']]);
            }

            $start = (int) ($request->data()['start'] ?? 0);
            $count = match ($start) {
                0, 50 => 50,
                100 => 10,
                default => 0,
            };
            $rows = $count > 0 ? $this->groupRows($start + 1, $count) : [];

            return Http::response($this->nestedPayload($rows, $start, 115, 50));
        });

        $report = app(DisbunReferenceSyncService::class)->syncAllReferences();
        $stats = $report['kelompok_tani'];

        $this->assertSame(110, $stats['fetched']);
        $this->assertSame(110, $stats['unique_external_ids']);
        $this->assertSame(100, $stats['terminal_short_page_start']);
        $this->assertSame(150, $stats['source_exhausted_at']);
        $this->assertSame(5, $stats['metadata_gap']);
        $this->assertTrue($stats['source_warning']);
        $this->assertContains('metadata_count_mismatch', $stats['warning_reasons']);
        $this->assertSame(110, RefKelompokTani::count());
    }

    public function test_conflicting_duplicate_gagal_sebelum_memutasi_last_good_dataset(): void
    {
        RefKomoditas::create(['kode' => 'OLD', 'nama' => 'Last Good', 'source' => RefKomoditas::SOURCE_DISBUN, 'disbun_record_id' => 700, 'is_verified' => true, 'sync_status' => RefKomoditas::SYNC_SYNCED]);
        RefKelompokTani::create(['disbun_record_id' => '700', 'nama' => 'Last Good Poktan', 'is_verified' => true, 'sync_status' => RefKelompokTani::SYNC_SYNCED]);

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/api/komoditas')) {
                return Http::response([['id' => 40, 'kode' => null, 'nama' => 'Kopi Robusta']]);
            }

            $start = (int) ($request->data()['start'] ?? 0);
            $rows = $start === 0 ? [
                ['id' => 9, 'kode_kelompok' => 'KT-9', 'nama_kelompok' => 'Poktan A', 'kabupaten' => 'Bandung'],
                ['id' => 9, 'kode_kelompok' => 'KT-9', 'nama_kelompok' => 'Poktan B', 'kabupaten' => 'Garut'],
            ] : [];

            return Http::response($this->nestedPayload($rows, $start, 2, 50));
        });

        $this->expectException(DisbunReferenceSyncException::class);
        $this->expectExceptionMessage('Conflicting duplicate external ID kelompok tani: 9');

        try {
            app(DisbunReferenceSyncService::class)->syncAllReferences();
        } finally {
            $this->assertDatabaseHas('ref_kelompok_tani', [
                'disbun_record_id' => '700',
                'nama' => 'Last Good Poktan',
            ]);
            $this->assertSame(1, RefKelompokTani::count());
            $this->assertSame(1, RefKomoditas::where('source', RefKomoditas::SOURCE_DISBUN)->count());
        }
    }

    public function test_command_melaporkan_success_with_source_warning(): void
    {
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/api/komoditas')) {
                return Http::response([['id' => 40, 'kode' => null, 'nama' => 'Kopi Robusta']]);
            }

            $start = (int) ($request->data()['start'] ?? 0);
            $count = match ($start) {
                0, 50 => 50,
                100 => 10,
                default => 0,
            };

            return Http::response($this->nestedPayload(
                $count > 0 ? $this->groupRows($start + 1, $count) : [],
                $start,
                115,
                50,
            ));
        });

        $this->artisan('disbun:sync-references')
            ->expectsOutputToContain('Raw fetched: 110')
            ->expectsOutputToContain('Unique records: 110')
            ->expectsOutputToContain('Terminal short page start: 100')
            ->expectsOutputToContain('Source exhausted at start: 150')
            ->expectsOutputToContain('Metadata gap: 5')
            ->expectsOutputToContain('Source warning: metadata_count_mismatch')
            ->expectsOutputToContain('WARNING: Disbun metadata count does not match records actually served by API.')
            ->expectsOutputToContain('Reference Sync: SUCCESS WITH SOURCE WARNING')
            ->assertSuccessful();
    }

    public function test_internal_selector_mencari_seluruh_reference_dengan_limit_server_side(): void
    {
        Role::findOrCreate('poktan');
        $user = User::factory()->create();
        $user->assignRole('poktan');

        foreach (range(1, 60) as $number) {
            RefKelompokTani::create([
                'disbun_record_id' => 'selector-'.$number,
                'nama' => 'Poktan Bandung '.$number,
                'kabupaten' => 'Kabupaten Bandung',
                'kecamatan' => 'Kecamatan '.$number,
                'kelurahan' => 'Kelurahan '.$number,
                'jenis_komoditi' => 'Kopi Robusta',
                'is_verified' => true,
                'sync_status' => RefKelompokTani::SYNC_SYNCED,
            ]);
        }

        RefKelompokTani::create([
            'disbun_record_id' => 'selector-kelurahan',
            'kode_kelompok' => 'KT-WM-001',
            'nama' => 'Wibawa Mukti',
            'kabupaten' => 'Kabupaten Garut',
            'kecamatan' => 'Tarogong',
            'kelurahan' => 'Sukajaya',
            'jenis_komoditi' => 'Kopi Robusta',
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_SYNCED,
        ]);

        $this->actingAs($user);
        $this->getJson('/internal/references/kelompok-tani?q=Bandung')
            ->assertOk()
            ->assertJsonCount(25, 'data')
            ->assertJsonMissingPath('data.0.ketua')
            ->assertJsonMissingPath('data.0.phone')
            ->assertJsonMissingPath('data.0.no_hp');
        $this->getJson('/internal/references/kelompok-tani?q=Poktan+Bandung+60')
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Poktan Bandung 60')
            ->assertJsonPath('data.0.id', 60);
        $this->getJson('/internal/references/kelompok-tani?q=Sukajaya')
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Wibawa Mukti')
            ->assertJsonPath('data.0.jenis_komoditi', 'Kopi Robusta')
            ->assertJsonPath('data.0.kelurahan', 'Sukajaya');
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function nestedPayload(array $rows, int $start, int $count, int $limit): array
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

    /** @return array<int, array<string, mixed>> */
    private function groupRows(int $firstId, int $count): array
    {
        $rows = [];
        foreach (range($firstId, $firstId + $count - 1) as $id) {
            $rows[] = [
                'id' => $id,
                'kode_kelompok' => 'KT-'.$id,
                'nama_kelompok' => 'Poktan '.$id,
                'jenis_komoditi' => 'Kopi Robusta',
                'kabupaten' => 'Kabupaten Bandung',
                'kecamatan' => 'Pangalengan',
            ];
        }

        return $rows;
    }
}
