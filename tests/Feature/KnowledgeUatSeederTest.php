<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\RefKomoditas;
use App\Models\Solusi;
use Database\Seeders\KnowledgeUatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeUatSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_uat_idempotent_dan_mengisi_dataset_lengkap(): void
    {
        foreach ([
            ['KP-079', 'Kopi Arabika'],
            ['KP-080', 'Kopi Robusta'],
            ['KP-017', 'Kakao'],
            ['KP-016', 'Cengkeh'],
            ['KP-056', 'Kelapa'],
        ] as [$code, $name]) {
            RefKomoditas::updateOrCreate(
                ['kode' => $code],
                ['nama' => $name, 'is_verified' => true, 'sync_status' => RefKomoditas::SYNC_SYNCED],
            );
        }

        $this->seed(KnowledgeUatSeeder::class);
        $this->seed(KnowledgeUatSeeder::class);

        $this->assertSame(4, Penyakit::where('kode', 'like', 'PNY-UAT-%')->count());
        $this->assertSame(24, Gejala::where('kode', 'like', 'G-UAT-%')->count());
        $this->assertSame(24, AturanCf::whereHas('penyakit', fn ($q) => $q->where('kode', 'like', 'PNY-UAT-%'))->count());
        $this->assertSame(4, Solusi::whereHas('penyakit', fn ($q) => $q->where('kode', 'like', 'PNY-UAT-%'))->count());
        $this->assertSame(5, PenyakitKomoditas::whereHas('penyakit', fn ($q) => $q->where('kode', 'like', 'PNY-UAT-%'))->count());
        $this->assertSame(0, Penyakit::where('kode', 'like', 'PNY-UAT-%')->whereNotNull('image_path')->count());
        $this->assertSame(0, Gejala::where('kode', 'like', 'G-UAT-%')->whereNotNull('image_path')->count());

        $this->assertDatabaseHas('aturan_cf', [
            'penyakit_id' => Penyakit::where('kode', 'PNY-UAT-001')->value('id'),
            'gejala_id' => Gejala::where('kode', 'G-UAT-003')->value('id'),
            'cf_pakar' => 0.95,
        ]);
    }
}
