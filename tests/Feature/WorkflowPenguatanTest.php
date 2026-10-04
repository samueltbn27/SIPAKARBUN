<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Penguatan workflow (bagian A):
 *   A1 review wajib sebelum terima/tolak,
 *   A2 selesai hanya dari dalam_pelaksanaan + verifikasi Operator,
 *   A3 pembatalan kasus dini oleh Operator,
 *   A4 intervensi status oleh Operator.
 */
class WorkflowPenguatanTest extends TestCase
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

    private function buatPermohonanDiajukan(User $pemohon): PermohonanPenanganan
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

        return PermohonanPenanganan::factory()->diajukan()->create([
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'created_by' => $pemohon->id,
        ]);
    }

    private function buatKasusDiterima(User $pemohon, User $operator): KasusPenanganan
    {
        $permohonan = $this->buatPermohonanDiajukan($pemohon);

        Sanctum::actingAs($operator);
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/review")->assertOk();
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept", ['catatan' => 'ok'])->assertCreated();

        return $permohonan->fresh('kasus')->kasus;
    }

    private function tugaskan(User $operator, User $popt, KasusPenanganan $kasus): void
    {
        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/assign-popt", [
            'popt_id' => $popt->id,
            'deadline_at' => now()->addDays(7)->toDateTimeString(),
        ])->assertOk();
    }

    private function statusPopt(User $popt, KasusPenanganan $kasus, string $status)
    {
        Sanctum::actingAs($popt);

        return $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => $status, 'catatan' => 'c']);
    }

    private function selesaikanPenuh(User $popt, KasusPenanganan $kasus): void
    {
        // Alur main: accept assignment → siap → dalam_pelaksanaan →
        // selesai via Laporan Hasil Penanganan (legacy status 'selesai'
        // langsung ditolak).
        Sanctum::actingAs($popt);
        $assignment = PenugasanPopt::query()
            ->where('kasus_id', $kasus->id)
            ->where('status', PenugasanPopt::STATUS_AKTIF)
            ->firstOrFail();

        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/penugasan/{$assignment->id}/accept")->assertOk();

        foreach (['siap_dieksekusi', 'dalam_pelaksanaan'] as $status) {
            $this->statusPopt($popt, $kasus, $status)->assertOk();
        }

        Sanctum::actingAs($popt);
        $this->post("/api/popt/kasus/{$kasus->id}/selesaikan", [
            'ringkasan_tindakan' => 'Pemeriksaan dan tindakan pengendalian dilakukan.',
            'hasil_penanganan' => 'Gejala terkendali setelah tindakan lapangan.',
            'rekomendasi' => 'Lanjutkan pemantauan rutin.',
            'photos' => [UploadedFile::fake()->image('laporan-akhir.jpg')],
        ])->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | A1 — review wajib
    |--------------------------------------------------------------------------
    */

    public function test_web_accept_tanpa_review_ditolak(): void
    {
        $operator = $this->buatUser('operator_uptd');
        $permohonan = $this->buatPermohonanDiajukan($this->buatUser('poktan'));

        $this->actingAs($operator)
            ->from(route('operator.permohonan.show', $permohonan->id))
            ->post(route('operator.permohonan.accept', $permohonan->id), ['catatan' => 'ok'])
            ->assertRedirect(route('operator.permohonan.show', $permohonan->id))
            ->assertSessionHasErrors('permohonan_id');

        $this->assertSame(PermohonanPenanganan::STATUS_DIAJUKAN, $permohonan->refresh()->status);
        $this->assertDatabaseCount('kasus_penanganan', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | A2 — selesai ketat + verifikasi
    |--------------------------------------------------------------------------
    */

    public function test_popt_tidak_bisa_shortcut_ke_selesai(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $kasus);

        // POPT menerima penugasan dahulu (syarat aksi lanjutan main);
        // accept otomatis memindahkan kasus ke sedang_direview.
        Sanctum::actingAs($popt);
        $assignmentId = PenugasanPopt::query()
            ->where('kasus_id', $kasus->id)
            ->where('status', PenugasanPopt::STATUS_AKTIF)
            ->firstOrFail()->id;
        $this->postJson("/api/popt/penugasan/{$assignmentId}/accept")->assertOk();
        $this->assertSame(KasusPenanganan::STATUS_SEDANG_DIREVIEW, $kasus->refresh()->current_status);

        // sedang_direview → selesai ditolak (shortcut).
        $this->statusPopt($popt, $kasus, 'selesai')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        // Rantai penuh selesai via Laporan Hasil Penanganan.
        $this->statusPopt($popt, $kasus, 'siap_dieksekusi')->assertOk();
        $this->statusPopt($popt, $kasus, 'dalam_pelaksanaan')->assertOk();
        Sanctum::actingAs($popt);
        $this->post("/api/popt/kasus/{$kasus->id}/selesaikan", [
            'ringkasan_tindakan' => 'Pemeriksaan dan tindakan pengendalian dilakukan.',
            'hasil_penanganan' => 'Gejala terkendali setelah tindakan lapangan.',
            'rekomendasi' => 'Lanjutkan pemantauan rutin.',
            'photos' => [UploadedFile::fake()->image('laporan-akhir.jpg')],
        ])->assertOk();

        $this->assertSame(KasusPenanganan::STATUS_SELESAI, $kasus->refresh()->current_status);
    }

    public function test_operator_memverifikasi_kasus_selesai(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $kasus);
        $this->selesaikanPenuh($popt, $kasus);

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/verifikasi")
            ->assertOk()
            ->assertJsonPath('data.selesai_terverifikasi', true)
            ->assertJsonPath('data.verified_by', $operator->id);

        $kasus->refresh();
        $this->assertTrue($kasus->isSelesaiTerverifikasi());
        $this->assertNotNull($kasus->verified_at);

        // Verifikasi ganda ditolak.
        $this->postJson("/api/kasus/{$kasus->id}/verifikasi")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kasus_id']);
    }

    public function test_verifikasi_ditolak_untuk_kasus_belum_selesai(): void
    {
        $operator = $this->buatUser('operator_uptd');
        $kasus = $this->buatKasusDiterima($this->buatUser('poktan'), $operator);

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/verifikasi")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kasus_id']);
    }

    public function test_popt_tidak_bisa_memverifikasi(): void
    {
        $popt = $this->buatUser('popt');

        Sanctum::actingAs($popt);
        $this->postJson('/api/kasus/1/verifikasi')->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | A4 — intervensi status oleh Operator
    |--------------------------------------------------------------------------
    */

    public function test_operator_dapat_mengubah_status_kerja(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $kasus);
        $this->statusPopt($popt, $kasus, 'sedang_direview')->assertOk();
        $this->statusPopt($popt, $kasus, 'ditunda')->assertOk();

        // Operator menarik kembali kasus yang ditunda.
        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/status", ['status' => 'sedang_direview', 'catatan' => 'Lanjut tangani'])
            ->assertOk()
            ->assertJsonPath('data.status', 'sedang_direview');

        $this->assertDatabaseHas('riwayat_status_penanganan', [
            'kasus_id' => $kasus->id,
            'previous_status' => 'ditunda',
            'status' => 'sedang_direview',
            'actor_id' => $operator->id,
        ]);
    }

    public function test_operator_tidak_bisa_transisi_ilegal(): void
    {
        $operator = $this->buatUser('operator_uptd');
        $kasus = $this->buatKasusDiterima($this->buatUser('poktan'), $operator);

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/status", ['status' => 'selesai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_popt_tidak_bisa_memakai_endpoint_status_operator(): void
    {
        $popt = $this->buatUser('popt');

        Sanctum::actingAs($popt);
        $this->postJson('/api/kasus/1/status', ['status' => 'selesai'])->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | A3 — pembatalan kasus dini
    |--------------------------------------------------------------------------
    */

    public function test_operator_dapat_membatalkan_kasus_diterima(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $permohonanId = $kasus->permohonan_id;

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/batal", ['alasan' => 'Data diagnosis keliru, perlu diajukan ulang.'])
            ->assertOk();

        // Kasus terarsip (soft-delete), permohonan kembali bisa diputuskan.
        $this->assertSoftDeleted('kasus_penanganan', ['id' => $kasus->id]);
        $this->assertSame(
            PermohonanPenanganan::STATUS_SEDANG_DIREVIEW,
            PermohonanPenanganan::find($permohonanId)->status
        );
        $this->assertDatabaseCount('keputusan_permohonan', 0);
    }

    public function test_batal_menutup_penugasan_aktif(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $kasus);

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/batal", ['alasan' => 'Salah target POPT dan wilayah.'])
            ->assertOk();

        $this->assertDatabaseHas('penugasan_popt', [
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'status' => PenugasanPopt::STATUS_DICABUT,
        ]);
    }

    public function test_batal_ditolak_untuk_kasus_yang_sudah_dikerjakan(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $kasus);
        $this->statusPopt($popt, $kasus, 'sedang_direview')->assertOk();

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/batal", ['alasan' => 'Terlambat membatalkan.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kasus_id']);

        $this->assertDatabaseHas('kasus_penanganan', ['id' => $kasus->id]);
    }

    public function test_batal_butuh_alasan_minimal(): void
    {
        $operator = $this->buatUser('operator_uptd');
        $kasus = $this->buatKasusDiterima($this->buatUser('poktan'), $operator);

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/batal", ['alasan' => 'pendek'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alasan']);
    }

    public function test_permohonan_bekas_batal_bisa_diputuskan_ulang(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $kasus = $this->buatKasusDiterima($pemohon, $operator);
        $permohonanId = $kasus->permohonan_id;

        Sanctum::actingAs($operator);
        $this->postJson("/api/kasus/{$kasus->id}/batal", ['alasan' => 'Perlu verifikasi ulang lapangan.'])->assertOk();

        // Putuskan ulang → kasus baru lahir.
        $this->postJson("/api/operator/permohonan/{$permohonanId}/accept", ['catatan' => 'ok'])->assertCreated();

        $this->assertDatabaseHas('keputusan_permohonan', [
            'permohonan_id' => $permohonanId,
            'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITERIMA,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | B — antrian kasus untuk POPT
    |--------------------------------------------------------------------------
    */

    public function test_web_antrian_hanya_menampilkan_kasus_menunggu_penugasan(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');

        $menunggu = $this->buatKasusDiterima($pemohon, $operator);
        $diambil = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $diambil);

        $this->actingAs($popt);

        $this->get(route('popt.antrian'))
            ->assertOk()
            ->assertSee($menunggu->kasus_code)
            ->assertDontSee($diambil->kasus_code);
    }

    public function test_web_antrian_kosong_menampilkan_empty_state(): void
    {
        $popt = $this->buatUser('popt');

        $this->actingAs($popt);

        $this->get(route('popt.antrian'))
            ->assertOk()
            ->assertSee('Tidak ada kasus dalam antrian.');
    }

    public function test_api_antrian_hanya_menampilkan_kasus_diterima(): void
    {
        $pemohon = $this->buatUser('poktan');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt');

        $menunggu = $this->buatKasusDiterima($pemohon, $operator);
        $diambil = $this->buatKasusDiterima($pemohon, $operator);
        $this->tugaskan($operator, $popt, $diambil);
        $this->selesaikanPenuh($popt, $diambil);

        Sanctum::actingAs($popt);
        $response = $this->getJson('/api/popt/antrian')->assertOk();

        $codes = collect($response->json('data'))->pluck('kasus_code')->all();
        $this->assertContains($menunggu->kasus_code, $codes);
        $this->assertNotContains($diambil->kasus_code, $codes);
    }

    public function test_antrian_ditolak_untuk_role_lain(): void
    {
        $poktan = $this->buatUser('poktan');

        $this->actingAs($poktan);
        $this->get(route('popt.antrian'))->assertForbidden();

        Sanctum::actingAs($poktan);
        $this->getJson('/api/popt/antrian')->assertForbidden();
    }

    public function test_sidebar_popt_menampilkan_menu_antrian(): void
    {
        $popt = $this->buatUser('popt');

        $this->actingAs($popt);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Antrian Kasus');
    }
}
