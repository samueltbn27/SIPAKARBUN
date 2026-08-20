<?php

namespace Tests\Feature;

use App\Exceptions\KnowledgeApiException;
use App\Services\HttpKnowledgeApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test layer integrasi Knowledge API Mahasiswa 1 (HttpKnowledgeApiClient).
 *
 * Menggunakan Http::fake() supaya tidak bergantung pada server/DB nyata —
 * hanya menguji perilaku client terhadap request/response simulasi.
 */
class KnowledgeApiClientTest extends TestCase
{
    private const BASE_URL = 'http://knowledge.test';

    private function client(array $overrides = []): HttpKnowledgeApiClient
    {
        return new HttpKnowledgeApiClient(
            baseUrl: $overrides['base_url'] ?? self::BASE_URL,
            token: $overrides['token'] ?? 'rahasia-token',
            timeout: $overrides['timeout'] ?? 5,
        );
    }

    private function fakePenyakit(array $data = []): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response([
                'data' => $data !== [] ? $data : [
                    [
                        'id' => 1,
                        'kode' => 'PY-001',
                        'nama' => 'Karat Daun Kopi',
                        'deskripsi' => null,
                        'komoditas_id' => [1, 2],
                        'aturan_cf' => [
                            ['gejala_id' => 1, 'gejala_nama' => 'Bercak jingga', 'cf_pakar' => 0.9],
                        ],
                        'solusi' => [
                            ['judul' => 'Pangkas daun', 'deskripsi' => 'Buang daun terinfeksi.'],
                        ],
                        'updated_at' => '2026-08-12T10:00:00+00:00',
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_penyakit_mengirim_bearer_token_dan_timeout(): void
    {
        $captured = null;
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => function (Request $request, array $options) use (&$captured) {
                $captured = $options;

                return Http::response(['data' => []], 200);
            },
        ]);

        $this->client()->penyakit();

        $this->assertSame(5, $captured['timeout']);
        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer rahasia-token')
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    public function test_penyakit_dengan_filter_komoditas_mengirim_query_param(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response(['data' => []], 200),
        ]);

        $this->client()->penyakit(3);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::BASE_URL.'/api/penyakit?komoditas_id=3'
                && $request->data() === ['komoditas_id' => 3];
        });
    }

    public function test_penyakit_menormalkan_data_untuk_modul_diagnosis(): void
    {
        $this->fakePenyakit();

        $result = $this->client()->penyakit();
        $item = $result->first();

        $this->assertCount(1, $result);
        $this->assertSame(1, $item['id']);
        $this->assertSame('PY-001', $item['kode']);
        $this->assertSame('Karat Daun Kopi', $item['nama']);
        $this->assertSame([1, 2], $item['komoditas_id']);
        $this->assertIsFloat($item['aturan_cf'][0]['cf_pakar']);
        $this->assertSame(0.9, $item['aturan_cf'][0]['cf_pakar']);
        $this->assertSame(1, $item['aturan_cf'][0]['gejala_id']);
        $this->assertSame('Pangkas daun', $item['solusi'][0]['judul']);
    }

    public function test_gejala_mengambil_dan_menormalkan_data(): void
    {
        Http::fake([
            self::BASE_URL.'/api/gejala*' => Http::response([
                'data' => [
                    ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ],
            ], 200),
        ]);

        $result = $this->client()->gejala();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()['id']);
        $this->assertSame('Bercak jingga', $result->first()['nama']);
    }

    public function test_response_error_http_menjadi_knowledge_api_exception(): void
    {
        Http::fake([
            '*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('status 500');

        $this->client()->penyakit();
    }

    public function test_response_tanpa_key_data_dilempar_sebagai_exception(): void
    {
        Http::fake([
            '*' => Http::response(['foo' => 'bar'], 200),
        ]);

        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('"data"');

        $this->client()->penyakit();
    }

    public function test_item_tanpa_id_nama_dilempar_sebagai_exception(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [['kode' => 'X']]], 200),
        ]);

        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('id/nama');

        $this->client()->penyakit();
    }

    public function test_gagal_koneksi_menjadi_knowledge_api_exception(): void
    {
        Http::fake([
            '*' => function (): never {
                throw new ConnectionException('cURL error 7: gagal terhubung');
            },
        ]);

        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('Gagal terhubung ke Knowledge API');

        $this->client()->penyakit();
    }

    public function test_base_url_kosong_memicu_configuration_error(): void
    {
        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('KNOWLEDGE_API_BASE_URL');

        $this->client(['base_url' => ''])->penyakit();
    }

    public function test_token_kosong_memicu_configuration_error(): void
    {
        $this->expectException(KnowledgeApiException::class);
        $this->expectExceptionMessage('KNOWLEDGE_API_TOKEN');

        $this->client(['token' => ''])->penyakit();
    }
}
