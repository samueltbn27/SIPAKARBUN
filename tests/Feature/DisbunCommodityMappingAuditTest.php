<?php

namespace Tests\Feature;

use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Services\DisbunCommodityMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisbunCommodityMappingAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_mengklasifikasikan_data_aktual_tanpa_fuzzy_match(): void
    {
        $masters = collect([
            $this->commodity(1, 'Kopi Arabika'),
            $this->commodity(2, 'Kopi Robusta'),
            $this->commodity(3, 'Jarak Pagar'),
            $this->commodity(4, 'Seraiwangi'),
        ]);
        $mapper = app(DisbunCommodityMapper::class);
        $index = $mapper->index($masters);

        $this->assertSame('B_SAFE_EXPLICIT_ALIAS', $mapper->classify(' Sereh   Wangi ', $index)['classification']);
        $this->assertSame('Seraiwangi', $mapper->classify('Sereh Wangi', $index)['candidate_alias']);
        $this->assertSame('E_AMBIGUOUS', $mapper->classify('Kopi', $index)['classification']);
        $this->assertSame('E_AMBIGUOUS', $mapper->classify('Jarak', $index)['classification']);
        $this->assertSame('D_SOURCE_DATA_QUALITY', $mapper->classify('Lainnya', $index)['classification']);
    }

    public function test_command_hanya_menerapkan_alias_aman_dan_mempertahankan_raw_name(): void
    {
        $seraiwangi = $this->commodity(4, 'Seraiwangi');
        $this->commodity(1, 'Kopi Arabika');
        $this->commodity(2, 'Kopi Robusta');
        $this->commodity(3, 'Jarak Pagar');

        $this->group(1, 'Sereh Wangi');
        $this->group(2, 'Sereh Wangi');
        $this->group(3, 'Kopi');
        $this->group(4, 'Kopi');
        $this->group(5, 'Lainnya');
        $this->group(6, 'Jarak');

        $this->artisan('disbun:audit-commodity-mappings', ['--apply-safe-aliases' => true])
            ->expectsOutputToContain('Unresolved Poktan: 6')
            ->expectsOutputToContain('Unique raw names: 4')
            ->expectsOutputToContain('B_SAFE_EXPLICIT_ALIAS')
            ->expectsOutputToContain('E_AMBIGUOUS')
            ->expectsOutputToContain('D_SOURCE_DATA_QUALITY')
            ->expectsOutputToContain('Safely remapped: 2')
            ->expectsOutputToContain('Unresolved Poktan: 4')
            ->assertSuccessful();

        $this->assertSame(2, RefKelompokTani::where('commodity_mapping_status', 'mapped')->count());
        $this->assertSame(4, RefKelompokTani::where('commodity_mapping_status', 'unresolved')->count());
        $this->assertSame(2, RefKelompokTani::where('commodity_ref_id', $seraiwangi->id)->count());
        $this->assertSame(2, RefKelompokTani::where('jenis_komoditi', 'Sereh Wangi')->count());
        $this->assertSame(0, RefKelompokTani::whereIn('jenis_komoditi', ['Kopi', 'Jarak', 'Lainnya'])->where('commodity_mapping_status', 'mapped')->count());
    }

    private function commodity(int $externalId, string $name): RefKomoditas
    {
        return RefKomoditas::create([
            'disbun_record_id' => $externalId,
            'source' => RefKomoditas::SOURCE_DISBUN,
            'kode' => 'DISBUN-'.$externalId,
            'nama' => $name,
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKomoditas::SYNC_SYNCED,
        ]);
    }

    private function group(int $externalId, string $rawCommodity): RefKelompokTani
    {
        return RefKelompokTani::create([
            'disbun_record_id' => (string) $externalId,
            'source' => RefKelompokTani::SOURCE_DISBUN,
            'nama' => 'Poktan '.$externalId,
            'jenis_komoditi' => $rawCommodity,
            'external_commodity_name' => $rawCommodity,
            'commodity_mapping_status' => 'unresolved',
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_SYNCED,
        ]);
    }
}
