<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\LaporanGejala;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Poin 5 — rantai gejala baru end-to-end:
 *
 *   Gejala Baru → Kajian POPT → Review Operator → Relasi penyakit & CF
 *   → Publish → Dipakai Diagnosis berikutnya.
 *
 * Review Operator adalah gate ringan: relasi CF untuk gejala asal laporan
 * hanya boleh dibuat setelah kajian disetujui; publish gejala asal
 * laporan juga mensyaratkan persetujuan + observasi terisi.
 */
class GejalaBaruFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'operator_uptd', 'popt', 'poktan'] as $role) {
            Role::findOrCreate($role);
        }

        Storage::fake('public');
        Storage::fake('local');
    }

    private function buatUser(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function buatLaporan(User $poktan): LaporanGejala
    {
        return LaporanGejala::create([
            'report_code' => 'LG-'.fake()->unique()->numerify('##########'),
            'commodity_id' => 1,
            'commodity_name_snapshot' => 'Kopi Arabika',
            'description' => 'Muncul bercak putih baru yang belum pernah saya lihat sebelumnya.',
            'status' => LaporanGejala::STATUS_DIAJUKAN,
            'created_by' => $poktan->id,
        ]);
    }

    private function buatDraftDariPopt(LaporanGejala $laporan): Gejala
    {
        $this->actingAs($this->buatUser('popt'))->post(
            '/popt/laporan-gejala/'.$laporan->id.'/review',
            ['action' => 'buat_draft', 'draft_symptom_name' => 'Bercak putih baru']
        )->assertRedirect();

        return Gejala::findOrFail($laporan->fresh()->gejala_id);
    }

    private function setujuiOperator(LaporanGejala $laporan, User $operator): void
    {
        $this->actingAs($operator)->post(
            '/operator/laporan-gejala/'.$laporan->id.'/review',
            ['keputusan' => 'setuju']
        )->assertRedirect();
    }

    public function test_operator_dapat_menyetujui_dan_menolak_kajian(): void
    {
        $laporan = $this->buatLaporan($this->buatUser('poktan'));
        $this->buatDraftDariPopt($laporan);
        $operator = $this->buatUser('operator_uptd');

        // Antrean menampilkan kajian menunggu review.
        $this->actingAs($operator)->get('/operator/laporan-gejala')
            ->assertOk()
            ->assertSee($laporan->report_code)
            ->assertSee('Menunggu review');

        $this->actingAs($operator)->get('/operator/laporan-gejala/'.$laporan->id)
            ->assertOk()
            ->assertSee('Keputusan review')
            ->assertSee('Setujui kajian');

        $this->setujuiOperator($laporan, $operator);

        $this->assertDatabaseHas('laporan_gejala', [
            'id' => $laporan->id,
            'operator_review' => LaporanGejala::REVIEW_SETUJU,
            'operator_reviewed_by' => $operator->id,
        ]);
        $this->assertNotNull($laporan->fresh()->operator_reviewed_at);

        $this->actingAs($operator)->get('/operator/laporan-gejala/'.$laporan->id)
            ->assertOk()
            ->assertSee('Disetujui Operator')
            ->assertSee('Buat relasi penyakit');

        // Penolakan wajib bercatatan.
        $laporan2 = $this->buatLaporan($this->buatUser('poktan'));
        $this->buatDraftDariPopt($laporan2);

        $this->actingAs($operator)->post('/operator/laporan-gejala/'.$laporan2->id.'/review', [
            'keputusan' => 'ditolak',
        ])->assertSessionHasErrors('catatan');

        $this->actingAs($operator)->post('/operator/laporan-gejala/'.$laporan2->id.'/review', [
            'keputusan' => 'ditolak',
            'catatan' => 'Bukti foto tidak menunjukkan gejala yang jelas.',
        ])->assertRedirect();

        $this->assertDatabaseHas('laporan_gejala', [
            'id' => $laporan2->id,
            'operator_review' => LaporanGejala::REVIEW_DITOLAK,
        ]);
    }

    public function test_review_dibatasi_status_dan_role(): void
    {
        $poktan = $this->buatUser('poktan');
        $laporan = $this->buatLaporan($poktan);
        $operator = $this->buatUser('operator_uptd');

        // Belum menjadi draft → 409.
        $this->actingAs($operator)->post('/operator/laporan-gejala/'.$laporan->id.'/review', [
            'keputusan' => 'setuju',
        ])->assertConflict();

        $this->buatDraftDariPopt($laporan);

        // Role selain admin/operator ditolak.
        $this->actingAs($this->buatUser('popt'))->get('/operator/laporan-gejala')->assertForbidden();
        $this->actingAs($poktan)->post('/operator/laporan-gejala/'.$laporan->id.'/review', [
            'keputusan' => 'setuju',
        ])->assertForbidden();

        // Review ganda ditolak.
        $this->setujuiOperator($laporan, $operator);
        $this->actingAs($operator)->post('/operator/laporan-gejala/'.$laporan->id.'/review', [
            'keputusan' => 'ditolak',
            'catatan' => 'Terlambat.',
        ])->assertConflict();
    }

    public function test_relasi_cf_diblokir_sebelum_persetujuan(): void
    {
        $laporan = $this->buatLaporan($this->buatUser('poktan'));
        $draft = $this->buatDraftDariPopt($laporan);
        $penyakit = Penyakit::create([
            'kode' => 'PY-REL-1',
            'nama' => 'Karat Daun',
            'status' => Penyakit::STATUS_AKTIF,
        ]);
        $operator = $this->buatUser('operator_uptd');

        $payload = [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $draft->id,
            'cf_pakar' => 0.8,
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
        ];

        // Sebelum review: 422.
        $this->actingAs($operator)->post('/knowledge/aturan-cf', $payload)
            ->assertSessionHasErrors('gejala_id');
        $this->assertDatabaseCount('aturan_cf', 0);

        // Setelah ditolak: tetap 422.
        $this->actingAs($operator)->post('/operator/laporan-gejala/'.$laporan->id.'/review', [
            'keputusan' => 'ditolak',
            'catatan' => 'Tidak meyakinkan.',
        ])->assertRedirect();
        $this->actingAs($operator)->post('/knowledge/aturan-cf', $payload)
            ->assertSessionHasErrors('gejala_id');
        $this->assertDatabaseCount('aturan_cf', 0);
    }

    public function test_relasi_cf_dibuka_setelah_persetujuan_hingga_publish(): void
    {
        $laporan = $this->buatLaporan($this->buatUser('poktan'));
        $draft = $this->buatDraftDariPopt($laporan);
        $operator = $this->buatUser('operator_uptd');
        $this->setujuiOperator($laporan, $operator);

        $penyakit = Penyakit::create([
            'kode' => 'PY-REL-2',
            'nama' => 'Karat Daun',
            'status' => Penyakit::STATUS_AKTIF,
        ]);
        PenyakitKomoditas::create(['penyakit_id' => $penyakit->id, 'komoditas_id' => 1]);

        // Form create menawarkan draft yang disetujui + prefill.
        $this->actingAs($operator)->get('/knowledge/aturan-cf/create?gejala_id='.$draft->id)
            ->assertOk()
            ->assertSee('draft disetujui')
            ->assertSee($laporan->report_code);

        // Relasi tersimpan sebagai draft.
        $this->actingAs($operator)->post('/knowledge/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $draft->id,
            'cf_pakar' => 0.8,
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
        ])->assertRedirect();

        $aturan = AturanCf::firstOrFail();
        $this->assertSame(AturanCf::STATUS_DRAFT, $aturan->status);

        // Publish gejala mensyaratkan observasi terisi.
        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'Gejala', 'id' => $draft->id, 'status' => Gejala::STATUS_AKTIF,
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($operator)->put('/knowledge/gejala/'.$draft->id, [
            'kriteria_observasi' => 'Bercak putih berdiameter 2-5 mm pada daun muda.',
            'metode_pengamatan' => 'Visual langsung di lapangan pagi hari.',
        ])->assertRedirect();

        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'Gejala', 'id' => $draft->id, 'status' => Gejala::STATUS_AKTIF,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Gejala::STATUS_AKTIF, $draft->fresh()->status);

        // Publish aturan (provenance sudah lengkap sejak create).
        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'AturanCf', 'id' => $aturan->id, 'status' => AturanCf::STATUS_AKTIF,
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Gejala + aturan kini terekspos ke Diagnosis berikutnya.
        Sanctum::actingAs($operator);
        $this->getJson('/api/gejala?komoditas_id=1')
            ->assertOk()
            ->assertJsonFragment(['nama' => 'Bercak putih baru']);
        $this->getJson('/api/penyakit?komoditas_id=1')
            ->assertOk()
            ->assertJsonFragment(['nama' => 'Karat Daun']);
    }

    public function test_publish_gejala_diblokir_tanpa_persetujuan(): void
    {
        $laporan = $this->buatLaporan($this->buatUser('poktan'));
        $draft = $this->buatDraftDariPopt($laporan);
        $operator = $this->buatUser('operator_uptd');

        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'Gejala', 'id' => $draft->id, 'status' => Gejala::STATUS_AKTIF,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(Gejala::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_publikasi_menampilkan_antrean_gejala_baru(): void
    {
        $laporan = $this->buatLaporan($this->buatUser('poktan'));
        $this->buatDraftDariPopt($laporan);
        $operator = $this->buatUser('operator_uptd');

        $this->actingAs($operator)->get('/knowledge/publikasi')
            ->assertOk()
            ->assertSee('Antrean Gejala Baru')
            ->assertSee($laporan->report_code)
            ->assertSee('Menunggu review Operator');

        $this->setujuiOperator($laporan, $operator);

        $this->actingAs($operator)->get('/knowledge/publikasi')
            ->assertOk()
            ->assertSee('Siap direlasikan');
    }

    public function test_halaman_laporan_menampilkan_tahapan_rantai(): void
    {
        $poktan = $this->buatUser('poktan');
        $laporan = $this->buatLaporan($poktan);

        $this->actingAs($poktan)->get(route('diagnosis.reports.show', $laporan))
            ->assertOk()
            ->assertSee('Tahapan validasi')
            ->assertSee('Kajian POPT');

        $this->buatDraftDariPopt($laporan);
        $this->setujuiOperator($laporan, $this->buatUser('operator_uptd'));

        $this->actingAs($poktan)->get(route('diagnosis.reports.show', $laporan))
            ->assertOk()
            ->assertSee('disetujui');
    }
}
