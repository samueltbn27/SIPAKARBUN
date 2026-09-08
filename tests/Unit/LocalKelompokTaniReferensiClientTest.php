<?php

namespace Tests\Unit;

use App\Models\RefKelompokTani;
use App\Services\LocalKelompokTaniReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalKelompokTaniReferensiClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_list_keeps_case_location_coordinates(): void
    {
        $poktan = RefKelompokTani::create([
            'disbun_record_id' => '5488',
            'source' => RefKelompokTani::SOURCE_DISBUN,
            'kode' => 'KT-5488',
            'nama' => 'AROSTA',
            'jenis_komoditi' => 'Kopi Arabika',
            'kabupaten' => 'KAB SUMEDANG',
            'kecamatan' => 'Wado',
            'latitude' => -7.0029856,
            'longitude' => 108.133707,
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_SYNCED,
        ]);

        $client = new LocalKelompokTaniReferensiClient;

        $this->assertSame([
            'latitude' => -7.0029856,
            'longitude' => 108.133707,
        ], [
            'latitude' => $client->all()[0]['latitude'],
            'longitude' => $client->all()[0]['longitude'],
        ]);

        $this->assertSame(-7.0029856, $client->find($poktan->id)['latitude']);
        $this->assertSame(108.133707, $client->find($poktan->id)['longitude']);
    }
}
