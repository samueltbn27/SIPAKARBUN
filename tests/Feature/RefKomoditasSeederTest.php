<?php

namespace Tests\Feature;

use App\Models\RefKomoditas;
use Database\Seeders\RefKomoditasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\LocalKomoditasReferensiClient;
use Tests\TestCase;

class RefKomoditasSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_mengisi_komoditas_yang_terbaca_runtime(): void
    {
        $this->seed(RefKomoditasSeeder::class);

        $this->assertGreaterThan(0, RefKomoditas::runtimeTersedia()->count());
        $this->assertSame(
            RefKomoditas::SOURCE_DISBUN,
            RefKomoditas::query()->value('source'),
        );
        $this->assertNotEmpty((new LocalKomoditasReferensiClient)->all());
    }
}
