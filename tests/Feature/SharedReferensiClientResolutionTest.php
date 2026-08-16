<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Services\HttpKelompokTaniReferensiClient;
use App\Services\HttpKomoditasReferensiClient;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test resolusi Dependency Injection client referensi Shared Integration:
 *  - Selama SHARED_API_BASE_URL kosong → otomatis memakai MOCK.
 *  - Ketika SHARED_API_BASE_URL diisi → beralih ke HTTP client yang
 *    memanggil endpoint Integration (tanpa mengubah kode business logic).
 */
class SharedReferensiClientResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.shared_referensi.base_url' => '']);
    }

    public function test_komoditas_resolves_mock_saat_base_url_kosong(): void
    {
        $client = app(KomoditasReferensiClient::class);

        $this->assertInstanceOf(MockKomoditasReferensiClient::class, $client);
    }

    public function test_kelompok_tani_resolves_mock_saat_base_url_kosong(): void
    {
        $client = app(KelompokTaniReferensiClient::class);

        $this->assertInstanceOf(MockKelompokTaniReferensiClient::class, $client);
    }

    public function test_komoditas_resolves_http_saat_base_url_diisi(): void
    {
        config(['services.shared_referensi.base_url' => 'http://integration.test']);

        Http::fake([
            '*/api/referensi/komoditas' => Http::response([
                'data' => [
                    ['id' => 1, 'kode' => 'KP-079', 'nama' => 'Kopi Arabika', 'is_active' => true],
                ],
            ], 200),
        ]);

        $client = app(KomoditasReferensiClient::class);

        $this->assertInstanceOf(HttpKomoditasReferensiClient::class, $client);
        $this->assertCount(1, $client->all());
        $this->assertSame('Kopi Arabika', $client->find(1)['nama']);
    }

    public function test_kelompok_tani_resolves_http_saat_base_url_diisi(): void
    {
        config(['services.shared_referensi.base_url' => 'http://integration.test']);

        Http::fake([
            '*/api/referensi/kelompok-tani' => Http::response([
                'data' => [
                    ['id' => 3, 'kode' => 'KT-003', 'nama' => 'Poktan Karet Mandiri', 'is_active' => true],
                ],
            ], 200),
        ]);

        $client = app(KelompokTaniReferensiClient::class);

        $this->assertInstanceOf(HttpKelompokTaniReferensiClient::class, $client);
        $this->assertCount(1, $client->all());
        $this->assertSame('Poktan Karet Mandiri', $client->find(3)['nama']);
    }

    public function test_http_client_mengembalikan_kosong_saat_service_down(): void
    {
        config(['services.shared_referensi.base_url' => 'http://integration.test']);

        // Integration API down → HTTP 500. Client tidak boleh crash;
        // ia mengembalikan array kosong (data "tidak tersedia").
        Http::fake([
            '*/api/referensi/kelompok-tani' => Http::response([], 500),
        ]);

        $client = app(KelompokTaniReferensiClient::class);

        $this->assertSame([], $client->all());
        $this->assertNull($client->find(3));
    }
}
