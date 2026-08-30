<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test alur kasus penanganan & POPT (kontrak §13-17):
 *   assign-popt, daftar/detail kasus operator, riwayat status,
 *   endpoint /api/popt/*, 403 lintas POPT, state machine.
 */
class KasusFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'admin', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function buatUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Buat kasus penanganan berstatus 'diterima' lewat alur nyata:
     * diagnosis → permohonan → operator accept.
     */
    private function buatKasusDiterima(User $pemohon, User $operator): KasusPenanganan
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $pemohon->id,
            'commodity_id' => 1,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        DiagnosisResult::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'cf_value' => 0.9,
            'ranking' => 1,
        ]);

        $permohonan = PermohonanPenanganan::factory()->diajukan()->create([
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'created_by' => $pemohon->id,
        ]);

        Sanctum::actingAs($operator);
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept", ['catatan' => 'ok'])
            ->assertCreated();

        return $permohonan->fresh('kasus')->kasus;
    }

    private function assignPopt(User $operator, User $popt, KasusPenanganan $kasus, ?string $catatan = null)
    {
        Sanctum::actingAs($operator);

        return $this->postJson("/api/kasus/{$kasus->id}/assign-popt", [
            'popt_id' => $popt->id,
            'catatan' => $catatan,
        ]);
    }

    private function selesaikanKasus(User $popt, KasusPenanganan $kasus): void
    {
        Sanctum::actingAs($popt);

        foreach (['sedang_direview', 'siap_dieksekusi', 'dalam_pelaksanaan', 'selesai'] as $status) {
            $this->postJson("/api/popt/kasus/{$kasus->id}/status", [
                'status' => $status,
                'catatan' => 'Catatan '.$status,
            ])->assertOk();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Penugasan POPT
    |--------------------------------------------------------------------------
    */

    public function test_operator_menugaskan_popt_yang_valid(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);

        $this->assignPopt($operator, $popt, $kasus, 'Kerjakan segera.')
            ->assertOk()
            ->assertJsonPath('data.status', KasusPenanganan::STATUS_DITUGASKAN)
            ->assertJsonPath('data.penugasan_popt.popt_id', $popt->id)
            ->assertJsonPath('data.penugasan_popt.status', PenugasanPopt::STATUS_AKTIF);

        $this->assertDatabaseHas('penugasan_popt', [
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'status' => PenugasanPopt::STATUS_AKTIF,
        ]);

        $this->assertDatabaseHas('riwayat_status_penanganan', [
            'kasus_id' => $kasus->id,
            'previous_status' => KasusPenanganan::STATUS_DITERIMA,
            'status' => KasusPenanganan::STATUS_DITUGASKAN,
            'actor_id' => $operator->id,
        ]);
    }

    public function test_popt_yang_dipilih_harus_berperan_popt(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $bukanPopt = $this->buatUser('pimpinan');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);

        $this->assignPopt($operator, $bukanPopt, $kasus)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['popt_id']);

        $this->assertDatabaseCount('penugasan_popt', 0);
    }

    public function test_popt_nonaktif_tidak_bisa_ditugaskan(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $poptNonaktif = $this->buatUser('popt');
        $poptNonaktif->update(['is_active' => false]);
        $kasus = $this->buatKasusDiterima($pemohon, $operator);

        $this->assignPopt($operator, $poptNonaktif, $kasus)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['popt_id']);

        $this->assertDatabaseCount('penugasan_popt', 0);
    }

    public function test_poktan_tidak_bisa_menugaskan_popt(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);

        Sanctum::actingAs($pemohon);
        $this->postJson("/api/kasus/{$kasus->id}/assign-popt", ['popt_id' => $popt->id])
            ->assertForbidden();
    }

    public function test_kasus_selesai_tidak_bisa_diassign_lagi(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $kasus->update(['current_status' => KasusPenanganan::STATUS_SELESAI]);

        $this->assignPopt($operator, $popt, $kasus)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kasus_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Daftar & detail kasus operator
    |--------------------------------------------------------------------------
    */

    public function test_operator_melihat_daftar_kasus_dengan_filter_status(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');

        $kasusDiterima = $this->buatKasusDiterima($pemohon, $operator);
        $popt = $this->buatUser('popt');
        $kasusDitugaskan = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasusDitugaskan)->assertOk();

        Sanctum::actingAs($operator);

        $this->getJson('/api/kasus')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/kasus?status={$kasusDiterima->current_status}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kasus_id', $kasusDiterima->id);
    }

    public function test_detail_kasus_operator_menyertakan_penugasan_dan_riwayat(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();

        Sanctum::actingAs($operator);

        $this->getJson("/api/kasus/{$kasus->id}")
            ->assertOk()
            ->assertJsonPath('data.penugasan_popt.popt_id', $popt->id)
            ->assertJsonCount(2, 'data.riwayat_status'); // diterima + ditugaskan
    }

    public function test_riwayat_status_append_only_dan_terurut(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/assign-popt", ['popt_id' => $popt->id])->assertOk();

        $this->getJson("/api/kasus/{$kasus->id}/history")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', KasusPenanganan::STATUS_DITUGASKAN)
            ->assertJsonPath('data.1.status', KasusPenanganan::STATUS_DITERIMA);
    }

    /*
    |--------------------------------------------------------------------------
    | Endpoint POPT
    |--------------------------------------------------------------------------
    */

    public function test_popt_melihat_penugasan_aktifnya_saja(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $poptLain = $this->buatUser('popt');

        $kasusSaya = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasusSaya)->assertOk();

        $kasusLain = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $poptLain, $kasusLain)->assertOk();

        Sanctum::actingAs($popt);

        $this->getJson('/api/popt/penugasan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kasus_id', $kasusSaya->id);
    }

    public function test_popt_can_read_active_assigned_case(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();

        Sanctum::actingAs($popt);

        $this->getJson("/api/popt/kasus/{$kasus->id}")
            ->assertOk()
            ->assertJsonPath('data.kasus_id', $kasus->id)
            ->assertJsonPath('data.penugasan_popt.popt_id', $popt->id);
    }

    public function test_popt_can_read_completed_case_previously_assigned_to_self(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();
        $this->selesaikanKasus($popt, $kasus);

        Sanctum::actingAs($popt);

        $this->getJson("/api/popt/kasus/{$kasus->id}")
            ->assertOk()
            ->assertJsonPath('data.current_status', KasusPenanganan::STATUS_SELESAI)
            ->assertJsonPath('data.penugasan_popt.popt_id', $popt->id)
            ->assertJsonPath('data.penugasan_popt.status', PenugasanPopt::STATUS_SELESAI);

        $this->getJson('/api/kasus')
            ->assertOk()
            ->assertJsonPath('data.0.kasus_id', $kasus->id)
            ->assertJsonPath('data.0.current_status', KasusPenanganan::STATUS_SELESAI);

        $this->getJson("/api/kasus/{$kasus->id}/history")
            ->assertOk()
            ->assertJsonPath('kasus_id', $kasus->id);
    }

    public function test_popt_tidak_bisa_akses_kasus_penugasan_orang_lain(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $poptLain = $this->buatUser('popt');

        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $poptLain, $kasus)->assertOk();

        Sanctum::actingAs($popt);

        $this->getJson("/api/popt/kasus/{$kasus->id}")->assertForbidden();
        $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => 'sedang_direview'])
            ->assertForbidden();
    }

    public function test_popt_mengubah_status_sesuai_state_machine(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();

        Sanctum::actingAs($popt);

        $this->postJson("/api/popt/kasus/{$kasus->id}/status", [
            'status' => KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            'catatan' => 'Mulai pemeriksaan lapangan.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', KasusPenanganan::STATUS_SEDANG_DIREVIEW);

        $this->assertDatabaseHas('riwayat_status_penanganan', [
            'kasus_id' => $kasus->id,
            'previous_status' => KasusPenanganan::STATUS_DITUGASKAN,
            'status' => KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            'actor_id' => $popt->id,
        ]);
    }

    public function test_transisi_status_ilegal_ditolak(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();

        Sanctum::actingAs($popt);

        // Melompat dari 'ditugaskan' ke 'selesai' itu ILEGAL.
        $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => 'selesai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $kasus->refresh();
        $this->assertSame(KasusPenanganan::STATUS_DITUGASKAN, $kasus->current_status);
    }

    public function test_popt_cannot_mutate_completed_case(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();

        Sanctum::actingAs($popt);

        $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => 'sedang_direview'])->assertOk();
        $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => 'selesai', 'catatan' => 'Selesai dikerjakan.'])
            ->assertOk();

        $kasus->refresh();
        $this->assertSame(KasusPenanganan::STATUS_SELESAI, $kasus->current_status);

        $this->assertDatabaseHas('penugasan_popt', [
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'status' => PenugasanPopt::STATUS_SELESAI,
        ]);

        // Setelah selesai, penugasan POPT ditutup tetapi read historis tetap
        // tersedia; mutation berikutnya harus ditolak 403.
        $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => 'ditunda'])
            ->assertForbidden();
    }

    public function test_other_popt_cannot_read_completed_case(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $poptPemilik = $this->buatUser('popt');
        $poptLain = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $poptPemilik, $kasus)->assertOk();
        $this->selesaikanKasus($poptPemilik, $kasus);

        Sanctum::actingAs($poptLain);

        $this->getJson("/api/popt/kasus/{$kasus->id}")->assertForbidden();
        $this->getJson('/api/kasus')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/popt/penugasan')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_other_popt_cannot_mutate_case(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $poptPemilik = $this->buatUser('popt');
        $poptLain = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $poptPemilik, $kasus)->assertOk();

        Sanctum::actingAs($poptLain);

        $this->postJson("/api/popt/kasus/{$kasus->id}/status", [
            'status' => KasusPenanganan::STATUS_SEDANG_DIREVIEW,
        ])->assertForbidden();
    }

    public function test_completed_case_remains_visible_in_popt_assignment_history(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->assignPopt($operator, $popt, $kasus)->assertOk();
        $this->selesaikanKasus($popt, $kasus);

        Sanctum::actingAs($popt);

        $this->getJson('/api/popt/penugasan')
            ->assertOk()
            ->assertJsonPath('data.0.kasus_id', $kasus->id)
            ->assertJsonPath('data.0.current_status', KasusPenanganan::STATUS_SELESAI);

        $this->actingAs($popt)
            ->get(route('popt.penugasan.show', $kasus->id))
            ->assertOk()
            ->assertSee($kasus->kasus_code)
            ->assertSee('Kasus sudah selesai')
            ->assertSee('Riwayat penanganan')
            ->assertDontSee('Simpan Progres');
    }

    public function test_operator_tidak_bisa_akses_endpoint_popt(): void
    {
        $operator = $this->buatUser('operator_uptd');
        Sanctum::actingAs($operator);

        $this->getJson('/api/popt/penugasan')->assertForbidden();
        $this->postJson('/api/popt/kasus/1/status', ['status' => 'sedang_direview'])->assertForbidden();
    }

    public function test_popt_tidak_bisa_akses_endpoint_assign(): void
    {
        $popt = $this->buatUser('popt');
        Sanctum::actingAs($popt);

        $this->postJson('/api/kasus/1/assign-popt', ['popt_id' => 1])->assertForbidden();
    }
}
