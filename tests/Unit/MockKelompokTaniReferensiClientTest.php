<?php

namespace Tests\Unit;

use App\Services\MockKelompokTaniReferensiClient;
use PHPUnit\Framework\TestCase;

/**
 * Test murni (tanpa database) untuk client mock referensi Kelompok Tani.
 * Memastikan data dummy yang dipakai validasi kelompok_tani_id pada
 * permohonan penanganan berperilaku benar.
 */
class MockKelompokTaniReferensiClientTest extends TestCase
{
    public function test_all_mengembalikan_empat_kelompok_tani(): void
    {
        $client = new MockKelompokTaniReferensiClient;

        $this->assertCount(4, $client->all());
    }

    public function test_find_mengembalikan_data_yang_benar(): void
    {
        $client = new MockKelompokTaniReferensiClient;

        $kelompok = $client->find(1);

        $this->assertNotNull($kelompok);
        $this->assertEquals('KT-001', $kelompok['kode']);
        $this->assertEquals('Poktan Kopi Sejahtera', $kelompok['nama']);
        $this->assertTrue($kelompok['is_active']);
    }

    public function test_find_mengembalikan_null_untuk_id_tidak_ada(): void
    {
        $client = new MockKelompokTaniReferensiClient;

        $this->assertNull($client->find(999));
    }
}
