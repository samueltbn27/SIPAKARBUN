<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\KasusPenanganan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Integrasi E2E frontend Poktan (TAHAP 8).
 *
 * Melewati seluruh alur nyata user Poktan:
 *   dashboard → wizard diagnosis → hasil diagnosis → ajukan permohonan
 *   → daftar permohonan → detail permohonan (status + timeline).
 *
 * Tidak ada stubbing per-controller: service asli dipakai, Knowledge API
 * M1 disimulasikan via Http::fake, referensi Shared Integration memakai
 * MOCK agar deterministik.
 */
class PoktanWorkflowE2ETest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://knowledge.test';

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'admin', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }

        config(['services.knowledge_api.base_url' => self::BASE_URL]);
        config(['services.knowledge_api.token' => 'rahasia-token']);
    }

    private function fakeKnowledge(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response(['data' => [
                [
                    'id' => 1,
                    'kode' => 'PY-001',
                    'nama' => 'Karat Daun Kopi',
                    'deskripsi' => null,
                    'komoditas_id' => [1],
                    'aturan_cf' => [
                        ['gejala_id' => 1, 'gejala_nama' => 'Bercak jingga', 'cf_pakar' => 0.9],
                        ['gejala_id' => 2, 'gejala_nama' => 'Daun menguning', 'cf_pakar' => 0.7],
                    ],
                    'solusi' => [
                        ['judul' => 'Pangkas daun', 'deskripsi' => 'Buang daun terinfeksi.'],
                    ],
                    'updated_at' => '2026-08-12T10:00:00+00:00',
                ],
            ]], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ['id' => 2, 'kode' => 'GJ-002', 'nama' => 'Daun menguning', 'deskripsi' => null],
            ]], 200),
        ]);
    }

    private function buatPoktan(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('poktan');

        return $user;
    }

    public function test_alur_lengkap_poktan_terhubung_end_to_end(): void
    {
        $this->fakeKnowledge();
        $user = $this->buatPoktan();
        $this->actingAs($user);

        // 1) Dashboard shell.
        $this->get('/dashboard')->assertOk()
            ->assertSee('Diagnosis Saya')
            ->assertSee('Permohonan Saya');

        // 2) Wizard diagnosis.
        $this->get('/diagnosis')->assertOk()
            ->assertSee('Kopi Arabika')
            ->assertSee('Bercak jingga');

        // 3) Proses diagnosis → redirect ke hasil, tersimpan di DB.
        $response = $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2],
        ]);

        $diagnosis = Diagnosis::first();
        $this->assertNotNull($diagnosis);
        $this->assertSame($user->id, $diagnosis->user_id);
        $this->assertSame('selesai', $diagnosis->status);
        $this->assertSame(1, $diagnosis->results()->count());
        $this->assertSame('Karat Daun Kopi', $diagnosis->results()->first()->disease_name_snapshot);

        $response->assertRedirect(route('diagnosis.show', ['id' => $diagnosis->id]))
            ->assertSessionHas('success');

        // 4) Detail hasil diagnosis (read-only) → tombol Ajukan Penanganan.
        $this->get(route('diagnosis.show', ['id' => $diagnosis->id]))->assertOk()
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Ajukan Penanganan')
            ->assertSee('Peringkat #1');

        // 5) Form ajuan dengan diagnosis terpilih terhubung dari ID diagnosis.
        $this->get(route('permohonan.create', ['diagnosis_id' => $diagnosis->id]))->assertOk()
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Kopi Arabika');

        // 6) Simpan permohonan → redirect ke detail.
        $storeResponse = $this->post('/permohonan', [
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'latitude_kasus' => -6.921,
            'longitude_kasus' => 107.6169,
            'alamat_kasus' => 'Blok Cibeureum, Dusun Satu, Ciawi',
            'catatan_pemohon' => 'Banyak daun menguning, mohon ditindaklanjuti.',
        ]);

        $permohonan = PermohonanPenanganan::first();
        $this->assertNotNull($permohonan);
        $this->assertSame($diagnosis->id, $permohonan->diagnosis_id);
        $this->assertSame('diajukan', $permohonan->status);

        $storeResponse->assertRedirect(route('permohonan.show', ['id' => $permohonan->id]))
            ->assertSessionHas('success');

        // 7) Detail permohonan: status diajukan, POPT belum ada, timeline 1 entri.
        $this->get(route('permohonan.show', ['id' => $permohonan->id]))->assertOk()
            ->assertSee($permohonan->permohonan_code)
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Kopi Arabika')
            ->assertSee('Diajukan')
            ->assertSee('Permohonan Diajukan')
            ->assertSee('Belum ada kasus penanganan.')
            ->assertSee('Belum ada petugas yang ditugaskan.');

        // 8) Daftar permohonan menampilkan item + navigasi detail.
        $this->get(route('permohonan.index'))->assertOk()
            ->assertSee($permohonan->permohonan_code)
            ->assertSee('Detail');
    }

    public function test_diagnosis_tanpa_hasil_tidak_tersedia_untuk_diajukan(): void
    {
        $poktan = $this->buatPoktan();
        $this->actingAs($poktan);

        // Diagnosis valid (ada hasil) vs yatim (tanpa hasil) dari user sama.
        $valid = Diagnosis::factory()->create([
            'user_id' => $poktan->id,
            'commodity_id' => 1,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);
        DiagnosisResult::factory()->create([
            'diagnosis_id' => $valid->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'cf_value' => 0.9,
            'ranking' => 1,
        ]);

        $orphan = Diagnosis::factory()->create([
            'user_id' => $poktan->id,
            'commodity_id' => 1,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        $response = $this->get(route('permohonan.create'))->assertOk();

        $response->assertSee($valid->kode)
            ->assertSee('Karat Daun Kopi')
            ->assertDontSee($orphan->kode);

        // Diagnosis yatim tidak bisa dijadikan acuan permohonan sama sekali.
        $this->post('/permohonan', [
            'diagnosis_id' => $orphan->id,
            'kelompok_tani_id' => 1,
        ])->assertSessionHas('error', 'Diagnosis tidak ditemukan atau bukan milik Anda.');

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }
}