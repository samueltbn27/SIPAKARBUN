<?php

namespace Tests\Unit\Services;

use App\Contracts\KnowledgeApiClient;
use App\Services\KnowledgeService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Test murni (tanpa database) untuk KnowledgeService.
 *
 * Memakai stub KnowledgeApiClient agar tidak bergantung pada jaringan/HTTP.
 */
class KnowledgeServiceTest extends TestCase
{
    private function clientStub(): KnowledgeApiClient
    {
        return new class implements KnowledgeApiClient
        {
            public function penyakit(?int $komoditasId = null): Collection
            {
                return collect([
                    ['id' => 1, 'kode' => 'PY-1', 'nama' => 'Karat Daun', 'deskripsi' => null, 'komoditas_id' => [1], 'aturan_cf' => [], 'solusi' => [], 'updated_at' => null],
                    ['id' => 2, 'kode' => 'PY-2', 'nama' => 'Layu', 'deskripsi' => null, 'komoditas_id' => [1], 'aturan_cf' => [], 'solusi' => [], 'updated_at' => null],
                ]);
            }

            public function gejala(?int $komoditasId = null): Collection
            {
                return collect([
                    ['id' => 10, 'kode' => 'GJ-10', 'nama' => 'Bercak daun', 'deskripsi' => null],
                    ['id' => 20, 'kode' => 'GJ-20', 'nama' => 'Daun menggulung', 'deskripsi' => null],
                ]);
            }
        };
    }

    public function test_penyakit_meneruskan_data_dari_client(): void
    {
        $service = new KnowledgeService($this->clientStub());

        $penyakit = $service->penyakit(1);

        $this->assertCount(2, $penyakit);
        $this->assertSame('Karat Daun', $penyakit->first()['nama']);
    }

    public function test_gejala_meneruskan_data_dari_client(): void
    {
        $service = new KnowledgeService($this->clientStub());

        $gejala = $service->gejala(1);

        $this->assertCount(2, $gejala);
        $this->assertSame('Bercak daun', $gejala->first()['nama']);
    }

    public function test_nama_gejala_mengembalikan_map_id_ke_nama(): void
    {
        $service = new KnowledgeService($this->clientStub());

        $this->assertSame([10 => 'Bercak daun', 20 => 'Daun menggulung'], $service->namaGejala());
    }

    public function test_nama_penyakit_mengembalikan_map_id_ke_nama(): void
    {
        $service = new KnowledgeService($this->clientStub());

        $this->assertSame([1 => 'Karat Daun', 2 => 'Layu'], $service->namaPenyakit());
    }
}
