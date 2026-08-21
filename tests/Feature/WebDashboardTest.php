<?php

namespace Tests\Feature;

use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\KasusPenanganan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test dashboard Poktan (TAHAP 1):
 *   - 4 kartu ringkasan (diagnosis, permohonan, kasus aktif, kasus selesai).
 *   - Section "Aktivitas Terakhir" (diagnosis & permohonan terbaru).
 *   - Konten Poktan hanya untuk role poktan; dashboard role lain tidak berubah.
 *
 * Statistik dihitung dari data DB (relasi existing) — tanpa endpoint palsu.
 */
class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function buatDiagnosis(User $user, int $commodityId = 1): Diagnosis
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $user->id,
            'commodity_id' => $commodityId,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        DiagnosisResult::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'cf_value' => 0.9,
            'ranking' => 1,
        ]);

        return $diagnosis;
    }

    private function buatPermohonan(User $user, Diagnosis $diagnosis): PermohonanPenanganan
    {
        return PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ringkasan & akses
    |--------------------------------------------------------------------------
    */

    public function test_tamu_dashboard_dialihkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_poktan_melihat_empat_kartu_ringkasan(): void
    {
        $this->actingAs($this->buatUser('poktan'));

        $response = $this->get('/dashboard')->assertOk();

        $response->assertSee('Diagnosis Saya')
            ->assertSee('Permohonan Saya')
            ->assertSee('Kasus Aktif')
            ->assertSee('Kasus Selesai')
            ->assertDontSee('Permohonan Masuk')
            ->assertDontSee('Kasus Berjalan');
    }

    public function test_poktan_melihat_angka_statistik_yang_benar(): void
    {
        $user = $this->buatUser('poktan');

        // 2 diagnosis, 4 permohonan (2 kasus aktif, 1 kasus selesai, 1 tanpa kasus).
        $d1 = $this->buatDiagnosis($user);
        $d2 = $this->buatDiagnosis($user);

        $p1 = $this->buatPermohonan($user, $d1);
        $p2 = $this->buatPermohonan($user, $d1);
        $p3 = $this->buatPermohonan($user, $d2);
        $this->buatPermohonan($user, $d2);

        KasusPenanganan::factory()->create([
            'permohonan_id' => $p1->id,
            'current_status' => KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
        ]);
        KasusPenanganan::factory()->create([
            'permohonan_id' => $p2->id,
            'current_status' => KasusPenanganan::STATUS_DITUGASKAN,
        ]);
        KasusPenanganan::factory()->create([
            'permohonan_id' => $p3->id,
            'current_status' => KasusPenanganan::STATUS_SELESAI,
        ]);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $response->assertSee('>2</span>', false)  // diagnosis
            ->assertSee('>4</span>', false)      // permohonan
            ->assertSee('>2</span>', false)      // kasus aktif
            ->assertSee('>1</span>', false);     // kasus selesai
    }

    public function test_poktan_aktivitas_terakhir_menampilkan_diagnosis_dan_permohonan(): void
    {
        $user = $this->buatUser('poktan');
        $diagnosis = $this->buatDiagnosis($user);
        $permohonan = $this->buatPermohonan($user, $diagnosis);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $response->assertSee('Aktivitas Terakhir')
            ->assertSee('Diagnosis Terbaru')
            ->assertSee('Permohonan Terbaru')
            ->assertSee($diagnosis->kode)
            ->assertSee('Karat Daun Kopi')
            ->assertSee($permohonan->permohonan_code)
            ->assertSee('Diajukan')
            ->assertSee('Belum Ada Kasus');
    }

    public function test_poktan_baru_melihat_empty_state(): void
    {
        $response = $this->actingAs($this->buatUser('poktan'))->get('/dashboard')->assertOk();

        $response->assertSee('Belum ada diagnosis')
            ->assertSee('Belum ada permohonan')
            ->assertSee('>0</span>', false);
    }

    public function test_poktan_tidak_melihat_menu_role_lain(): void
    {
        $this->actingAs($this->buatUser('poktan'));

        $response = $this->get('/dashboard')->assertOk();

        $response->assertDontSee('Permohonan Masuk')
            ->assertDontSee('Penugasan Saya')
            ->assertDontSee('Pengguna');
    }

    public function test_dashboard_role_lain_tetap_normal(): void
    {
        $this->actingAs($this->buatUser('operator_uptd'));

        $response = $this->get('/dashboard')->assertOk();

        $response->assertSee('Permohonan Masuk')
            ->assertSee('Kasus Berjalan')
            ->assertDontSee('Aktivitas Terakhir')
            ->assertDontSee('Kasus Aktif');
    }

    public function test_dashboard_komoditas_gagal_tetap_render(): void
    {
        app()->instance(KomoditasReferensiClient::class, new class implements KomoditasReferensiClient
        {
            public function all(): array
            {
                throw new \RuntimeException('referensi komoditas down');
            }

            public function find(int $id): ?array
            {
                return null;
            }
        });

        $user = $this->buatUser('poktan');
        $this->buatDiagnosis($user);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Komoditas #1');
    }
}
