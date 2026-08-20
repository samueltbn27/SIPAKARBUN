<?php

namespace Tests\Unit;

use App\Services\MockKomoditasReferensiClient;
use PHPUnit\Framework\TestCase;

/**
 * Test murni (tanpa database) untuk client mock tahap #8 — memastikan
 * data dummy yang dipakai untuk validasi komoditas_id di
 * StorePenyakitRequest/UpdatePenyakitRequest berperilaku benar.
 */
class MockKomoditasReferensiClientTest extends TestCase
{
    public function test_all_mengembalikan_data_komoditas(): void
    {
        $client = new MockKomoditasReferensiClient();

        $this->assertCount(41, $client->all());
    }

    public function test_find_mengembalikan_data_yang_benar(): void
    {
        $client = new MockKomoditasReferensiClient();

        $kopi = $client->find(1);

        $this->assertNotNull($kopi);
        $this->assertEquals('KP-079', $kopi['kode']);
        $this->assertEquals('Kopi Arabika', $kopi['nama']);
    }

    public function test_find_mengembalikan_null_untuk_id_tidak_ada(): void
    {
        $client = new MockKomoditasReferensiClient();

        $this->assertNull($client->find(999));
    }
}
