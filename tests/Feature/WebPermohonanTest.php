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
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test modul Web Permohonan Penanganan Poktan (TAHAP 4):
 *   GET  /permohonan           (index)
 *   GET  /permohonan/create    (form -> pilih diagnosis)
 *   POST /permohonan           (store)
 *   GET  /permohonan/{id}      (detail, hanya milik sendiri)
 *
 * Bergerak menempel pada PermohonanService/StorePermohonanRequest yang sama
 * dengan API; referensi Shared Integration di-pasang eksplisit ke MOCK
 * agar test deterministik. Lokasi kasus selalu input terpisah dari lokasi
 * kelompok tani (kontrak §10).
 */
class WebPermohonanTest extends TestCase
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

        Storage::fake('public');
    }

    private function buatUserPoktan(): User
    {
        $user = User::factory()->create();
        $user->assignRole('poktan');

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

    private function buatPermohonan(User $user): PermohonanPenanganan
    {
        return PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($user)->id,
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ]);
    }

    private function payloadDasar(Diagnosis $diagnosis): array
    {
        return [
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'latitude_kasus' => -6.921,
            'longitude_kasus' => 107.6169,
            'alamat_kasus' => 'Blok Cibeureum, Dusun Satu, Ciawi',
            'catatan_pemohon' => 'Banyak daun menguning, mohon ditindaklanjuti.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Akses & guard role
    |--------------------------------------------------------------------------
    */

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/permohonan')->assertRedirect(route('login'));
        $this->get('/permohonan/create')->assertRedirect(route('login'));
        $this->get('/permohonan/1')->assertRedirect(route('login'));
        $this->post('/permohonan', [])->assertRedirect(route('login'));
    }

    public function test_role_non_poktan_mendapat_403(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator_uptd');

        $this->actingAs($operator);
        $this->get('/permohonan')->assertForbidden();
        $this->get('/permohonan/create')->assertForbidden();
        $this->post('/permohonan', [])->assertForbidden();
        $this->get('/permohonan/1')->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Index (daftar permohonan saya)
    |--------------------------------------------------------------------------
    */

    public function test_index_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->buatUserPoktan());

        $this->get('/permohonan')
            ->assertOk()
            ->assertSee('Belum ada permohonan');
    }

    public function test_index_hanya_menampilkan_permohonan_milik_pemohon(): void
    {
        $pemohon = $this->buatUserPoktan();
        $lain = $this->buatUserPoktan();

        $diagnosis = $this->buatDiagnosis($pemohon);

        $permohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $pemohon->id,
        ]);

        PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($lain)->id,
            'kelompok_tani_id' => 2,
            'kelompok_tani_name_snapshot' => 'Gapoktan Tani Makmur',
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $lain->id,
        ]);

        $this->actingAs($pemohon);

        $response = $this->get('/permohonan')->assertOk();

        $response->assertSee($permohonan->permohonan_code)
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Kopi Arabika')
            ->assertSee('Diajukan')
            ->assertDontSee('Gapoktan Tani Makmur');
    }

    public function test_index_memfilter_status(): void
    {
        $pemohon = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($pemohon);

        $ditunda = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $pemohon->id,
        ]);

        PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'status' => PermohonanPenanganan::STATUS_DITOLAK,
            'created_by' => $pemohon->id,
        ]);

        $this->actingAs($pemohon);

        $this->get('/permohonan?status=diajukan')
            ->assertOk()
            ->assertSee($ditunda->permohonan_code);

        $this->get('/permohonan?status=ditolak')
            ->assertOk()
            ->assertDontSee($ditunda->permohonan_code);
    }

    // ==== TAHAP 6 — Daftar Permohonan Poktan ====

    public function test_index_tabel_menampilkan_kolom_sesuai_spek(): void
    {
        $pemohon = $this->buatUserPoktan();
        $this->buatPermohonan($pemohon);

        $this->actingAs($pemohon);

        $this->get('/permohonan')->assertOk()
            ->assertSee('Kode Permohonan/Kasus')
            ->assertSee('Tanggal Pengajuan')
            ->assertSee('Komoditas')
            ->assertSee('Diagnosis')
            ->assertSee('Status Permohonan')
            ->assertSee('Status Penanganan')
            ->assertSee('Aksi');
    }

    public function test_index_membedakan_status_permohonan_dan_penanganan(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        // Permohonan tanpa kasus -> status penanganan "Belum Ditugaskan".
        $permohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->get('/permohonan')
            ->assertOk()
            ->assertSee($permohonan->permohonan_code)
            ->assertSee('Permohonan: Diajukan')
            ->assertSee('Diajukan')
            ->assertSee('Belum Ditugaskan');

        // Permohonan diterima + kasus ditugaskan -> status penanganan "Ditugaskan".
        $kasusPermohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($user)->id,
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
            'created_by' => $user->id,
        ]);

        KasusPenanganan::factory()->create([
            'permohonan_id' => $kasusPermohonan->id,
            'kasus_code' => 'KS-20260819-0007',
            'current_status' => KasusPenanganan::STATUS_DITUGASKAN,
        ]);

        $this->get('/permohonan')
            ->assertSee($kasusPermohonan->permohonan_code)
            ->assertSee('KS-20260819-0007')
            ->assertSee('Diterima')
            ->assertSee('Ditugaskan');
    }

    public function test_index_filter_tanggal(): void
    {
        $user = $this->buatUserPoktan();

        $baru = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($user)->id,
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $lama = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($user)->id,
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
            'created_at' => now()->subDays(3),
        ]);

        $this->actingAs($user);

        $this->get('/permohonan?created_from='.now()->toDateString())
            ->assertOk()
            ->assertSee($baru->permohonan_code)
            ->assertDontSee($lama->permohonan_code);

        $this->get('/permohonan?created_to='.now()->subDays(3)->toDateString())
            ->assertOk()
            ->assertSee($lama->permohonan_code)
            ->assertDontSee($baru->permohonan_code);
    }

    public function test_index_aksi_detail_menuju_halaman_detail(): void
    {
        $pemohon = $this->buatUserPoktan();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->actingAs($pemohon);

        $this->get('/permohonan')
            ->assertOk()
            ->assertSee('Detail')
            ->assertSee(route('permohonan.show', $permohonan->id));
    }

    /*
    |--------------------------------------------------------------------------
    | Create (pilih diagnosis & form)
    |--------------------------------------------------------------------------
    */

    public function test_create_menampilkan_pemilihan_diagnosis(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->get('/permohonan/create')
            ->assertOk()
            ->assertSee($diagnosis->kode)
            ->assertSee('Kopi Arabika')
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Ajukan Penanganan');
    }

    public function test_create_tanpa_diagnosis_selesai_menampilkan_empty_state(): void
    {
        $user = $this->buatUserPoktan();
        Diagnosis::factory()->create([
            'user_id' => $user->id,
            'status' => 'proses',
        ]);

        $this->actingAs($user);

        $this->get('/permohonan/create')
            ->assertOk()
            ->assertSee('Belum ada diagnosis untuk diajukan');
    }

    public function test_create_dengan_diagnosis_milik_user_menampilkan_form(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $response = $this->get('/permohonan/create?diagnosis_id='.$diagnosis->id)->assertOk();

        $response->assertSee('Kode Diagnosis')
            ->assertSee($diagnosis->kode)
            ->assertSee('Lokasi Kasus')
            ->assertSee('Kelompok Tani')
            ->assertSee('latitude_kasus')
            ->assertSee('longitude_kasus')
            ->assertSee('alamat_kasus')
            ->assertSee('Tinjau Permohonan');
    }

    public function test_create_dengan_diagnosis_di_luar_daftar_tampil_empty(): void
    {
        $user = $this->buatUserPoktan();
        $orangLain = $this->buatUserPoktan();
        $diagnosisLain = $this->buatDiagnosis($orangLain);

        $this->actingAs($user);

        $this->get('/permohonan/create?diagnosis_id='.$diagnosisLain->id)
            ->assertOk()
            ->assertSee('Belum ada diagnosis untuk diajukan');
    }

    // ==== TAHAP 5 — Form Permohonan Penanganan Poktan ====

    public function test_create_menampilkan_data_diagnosis_readonly(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $response = $this->get('/permohonan/create?diagnosis_id='.$diagnosis->id)->assertOk();

        $response->assertSee('Data Diagnosis')
            ->assertSee('Kode Diagnosis')
            ->assertSee($diagnosis->kode)
            ->assertSee('Kopi Arabika')
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Nilai CF')
            ->assertSee('0,90');
    }

    public function test_create_menampilkan_gejala_diagnosis_secara_readonly(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $diagnosis->symptoms()->createMany([
            ['symptom_id' => 1, 'symptom_name_snapshot' => 'Bercak jingga', 'cf_user' => 1.0],
            ['symptom_id' => 2, 'symptom_name_snapshot' => 'Daun menguning', 'cf_user' => 0.8],
        ]);

        $this->actingAs($user);

        $this->get('/permohonan/create?diagnosis_id='.$diagnosis->id)
            ->assertOk()
            ->assertSee('Gejala Dipilih')
            ->assertSee('Bercak jingga')
            ->assertSee('Daun menguning');
    }

    public function test_create_lokasi_kasus_terpisah_dari_lokasi_kelompok_tani(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->get('/permohonan/create?diagnosis_id='.$diagnosis->id)
            ->assertOk()
            ->assertSee('latitude_kasus')
            ->assertSee('longitude_kasus')
            ->assertSee('alamat_kasus')
            ->assertSee('terpisah dari lokasi kelompok tani');
    }

    public function test_create_menampilkan_aturan_validasi_bukti(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->get('/permohonan/create?diagnosis_id='.$diagnosis->id)
            ->assertOk()
            ->assertSee('Foto / Bukti')
            ->assertSee('JPG/PNG/WebP')
            ->assertSee('Validasi mengikuti backend');
    }

    /*
    |--------------------------------------------------------------------------
    | Store (POST /permohonan)
    |--------------------------------------------------------------------------
    */

    public function test_poktan_berhasil_mengajukan_permohonan(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $response = $this->post('/permohonan', $this->payloadDasar($diagnosis));

        $permohonan = PermohonanPenanganan::query()
            ->where('diagnosis_id', $diagnosis->id)
            ->firstOrFail();

        $response->assertRedirect(route('permohonan.show', ['id' => $permohonan->id]))
            ->assertSessionHas('success', 'Permohonan berhasil dikirim.');

        $this->assertSame('PM-'.now()->format('Ymd').'-0001', $permohonan->permohonan_code);
        $this->assertSame(PermohonanPenanganan::STATUS_DIAJUKAN, $permohonan->status);
        $this->assertSame(1, $permohonan->kelompok_tani_id);
        $this->assertSame('Poktan Kopi Sejahtera', $permohonan->kelompok_tani_name_snapshot);
        $this->assertSame($user->id, $permohonan->created_by);
        $this->assertSame('Blok Cibeureum, Dusun Satu, Ciawi', $permohonan->alamat_kasus);
    }

    public function test_store_dengan_file_bukti_menyimpan_evidence(): void
    {
        Storage::fake('public');

        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->image('bukti.png', 200, 200),
            ],
        ]))->assertRedirect();

        $this->assertDatabaseHas('permohonan_penanganan', [
            'diagnosis_id' => $diagnosis->id,
            'created_by' => $user->id,
        ]);

        $permohonan = PermohonanPenanganan::query()
            ->where('diagnosis_id', $diagnosis->id)
            ->firstOrFail();

        $this->assertSame(1, $permohonan->evidences()->count());

        $evidence = $permohonan->evidences()->first();
        Storage::disk('public')->assertExists($evidence->file_path);
    }

    public function test_store_menolak_diagnosis_milik_user_lain(): void
    {
        $user = $this->buatUserPoktan();
        $orangLain = $this->buatUserPoktan();
        $diagnosisLain = $this->buatDiagnosis($orangLain);

        $this->actingAs($user);

        $this->post('/permohonan', $this->payloadDasar($diagnosisLain))
            ->assertSessionHas('error', 'Diagnosis tidak ditemukan atau bukan milik Anda.');

        $this->assertDatabaseMissing('permohonan_penanganan', ['created_by' => $user->id]);
    }

    public function test_store_menolak_kelompok_tani_tidak_valid(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'kelompok_tani_id' => 999,
        ]))->assertSessionHasErrors('kelompok_tani_id');

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_store_validasi_latitude_luar_rentang(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'latitude_kasus' => 91,
        ]))->assertSessionHasErrors('latitude_kasus');
    }

    public function test_store_validasi_longitude_luar_rentang(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'longitude_kasus' => 181,
        ]))->assertSessionHasErrors('longitude_kasus');
    }

    public function test_store_tanpa_lokasi_kasus_selaras_backend_nullable(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', [
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'catatan_pemohon' => 'Tanpa koordinat — Operator dapat melengkapi di tahap kasus.',
        ])->assertRedirect();

        $permohonan = PermohonanPenanganan::query()
            ->where('diagnosis_id', $diagnosis->id)
            ->firstOrFail();

        $this->assertNull($permohonan->latitude_kasus);
        $this->assertNull($permohonan->longitude_kasus);
        $this->assertNull($permohonan->alamat_kasus);
    }

    public function test_store_menolak_catatan_berlebih(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'catatan_pemohon' => str_repeat('c', 2001),
        ]))->assertSessionHasErrors('catatan_pemohon');
    }

    public function test_store_menolak_file_bukan_gambar(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ],
        ]))->assertSessionHasErrors('evidences.0');
    }

    public function test_store_menolak_file_melebihi_5mb(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('besar.png', 6000, 'image/png'),
            ],
        ]))->assertSessionHasErrors('evidences.0');
    }

    public function test_store_menolak_evidences_berlebih_lima(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->post('/permohonan', array_merge($this->payloadDasar($diagnosis), [
            'evidences' => array_map(
                static fn (int $i): UploadedFile => UploadedFile::fake()->image('b'.$i.'.png', 10, 10),
                range(1, 6),
            ),
        ]))->assertSessionHasErrors('evidences');
    }

    /*
    |--------------------------------------------------------------------------
    | Show (detail permohonan milik sendiri)
    |--------------------------------------------------------------------------
    */

    public function test_show_menampilkan_detail_permohonan(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $permohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'permohonan_code' => 'PM-20260818-0001',
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'latitude_kasus' => -6.921,
            'longitude_kasus' => 107.6169,
            'alamat_kasus' => 'Blok Cibeureum, Ciawi',
            'catatan_pemohon' => 'Banyak daun menguning.',
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get('/permohonan/'.$permohonan->id)->assertOk();

        $response->assertSee('PM-20260818-0001')
            ->assertSee('Diajukan')
            ->assertSee('Poktan Kopi Sejahtera')
            ->assertSee('Lokasi Kasus')
            ->assertSee('-6,9210000')
            ->assertSee('107,6169000')
            ->assertSee('Blok Cibeureum, Ciawi')
            ->assertSee('Banyak daun menguning.')
            ->assertSee('Karat Daun Kopi')
            ->assertSee('Kopi Arabika');
    }

    public function test_show_kepemilikan_dibatasi_user_lain(): void
    {
        $pemohon = $this->buatUserPoktan();
        $lain = $this->buatUserPoktan();

        $permohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($pemohon)->id,
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $pemohon->id,
        ]);

        $this->actingAs($lain);

        $this->get('/permohonan/'.$permohonan->id)->assertNotFound();
    }

    public function test_show_id_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->buatUserPoktan());

        $this->get('/permohonan/99999')->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Integrasi: hasil diagnosis punya tombol Ajukan Penanganan
    |--------------------------------------------------------------------------
    */

    public function test_halaman_hasil_diagnosis_menyediakan_tombol_ajukan_penanganan(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);

        $this->actingAs($user);

        $this->get(route('diagnosis.show', ['id' => $diagnosis->id]))
            ->assertOk()
            ->assertSee('Ajukan Penanganan')
            ->assertSee('diagnosis_id='.$diagnosis->id);
    }

    /*
    |--------------------------------------------------------------------------
    | TAHAP 7 — Detail Permohonan/Kasus Poktan (READ ONLY)
    |--------------------------------------------------------------------------
    */

    private function buatUserDenganRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Bangun permohonan DITERIMA lengkap dengan keputusan, kasus, penugasan
     * POPT, dan riwayat status sampai tahap tertentu.
     *
     * @return array{0: PermohonanPenanganan, 1: KasusPenanganan}
     */
    private function buatKasusTerlacak(
        User $pemohon,
        User $operator,
        User $popt,
        string $kasusStatus = KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
    ): array {
        $permohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($pemohon)->id,
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
            'created_by' => $pemohon->id,
            'created_at' => now()->subDays(5),
        ]);

        $decidedAt = now()->subDays(5)->addHours(2);

        KeputusanPermohonan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITERIMA,
            'catatan' => 'Layak ditindaklanjuti.',
            'operator_id' => $operator->id,
            'decided_at' => $decidedAt,
        ]);

        $kasus = KasusPenanganan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'kasus_code' => 'KS-20260819-0007',
            'current_status' => $kasusStatus,
            'komoditas_name_snapshot' => 'Kopi Arabika',
            'penyakit_name_snapshot' => 'Karat Daun Kopi',
            'created_by' => $operator->id,
            'created_at' => $decidedAt,
        ]);

        // Status awal kasus (kelahiran) — di page direpresentasikan sebagai
        // "Permohonan Diterima", sehingga tidak tampil sebagai baris tersendiri.
        RiwayatStatusPenanganan::factory()->create([
            'kasus_id' => $kasus->id,
            'previous_status' => null,
            'status' => KasusPenanganan::STATUS_DITERIMA,
            'catatan' => 'Kasus lahir dari permohonan yang diterima.',
            'actor_id' => $operator->id,
            'created_at' => $decidedAt,
        ]);

        if ($kasusStatus === KasusPenanganan::STATUS_DITUGASKAN
            || $kasusStatus === KasusPenanganan::STATUS_DALAM_PELAKSANAAN
            || $kasusStatus === KasusPenanganan::STATUS_SELESAI) {
            RiwayatStatusPenanganan::factory()->create([
                'kasus_id' => $kasus->id,
                'previous_status' => KasusPenanganan::STATUS_DITERIMA,
                'status' => KasusPenanganan::STATUS_DITUGASKAN,
                'catatan' => 'POPT ditugaskan. Kerjakan dengan teliti.',
                'actor_id' => $operator->id,
                'created_at' => now()->subDays(4),
            ]);

            PenugasanPopt::factory()->aktif()->create([
                'kasus_id' => $kasus->id,
                'popt_id' => $popt->id,
                'assigned_by' => $operator->id,
                'catatan' => 'Kerjakan segera.',
                'assigned_at' => now()->subDays(4),
            ]);
        }

        if ($kasusStatus === KasusPenanganan::STATUS_DALAM_PELAKSANAAN
            || $kasusStatus === KasusPenanganan::STATUS_SELESAI) {
            RiwayatStatusPenanganan::factory()->create([
                'kasus_id' => $kasus->id,
                'previous_status' => KasusPenanganan::STATUS_DITUGASKAN,
                'status' => KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
                'catatan' => 'Mulai pelaksanaan di lapangan.',
                'actor_id' => $popt->id,
                'created_at' => now()->subDays(1),
            ]);
        }

        if ($kasusStatus === KasusPenanganan::STATUS_SELESAI) {
            RiwayatStatusPenanganan::factory()->create([
                'kasus_id' => $kasus->id,
                'previous_status' => KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
                'status' => KasusPenanganan::STATUS_SELESAI,
                'catatan' => 'Penanganan selesai.',
                'actor_id' => $popt->id,
                'created_at' => now(),
            ]);
        }

        return [$permohonan, $kasus];
    }

    public function test_show_menampilkan_status_permohonan_dan_penanganan_terpisah(): void
    {
        $pemohon = $this->buatUserPoktan();
        $operator = $this->buatUserDenganRole('operator_uptd');
        $popt = $this->buatUserDenganRole('popt');

        [$permohonan, $kasus] = $this->buatKasusTerlacak($pemohon, $operator, $popt);

        $this->actingAs($pemohon);

        $this->get('/permohonan/'.$permohonan->id)
            ->assertOk()
            ->assertSee('Status Permohonan')
            ->assertSee('Status Penanganan')
            ->assertSee('Diterima')
            ->assertSee('Dalam Pelaksanaan')
            ->assertSee($kasus->kasus_code);
    }

    public function test_show_tanpa_kasus_menampilkan_status_penanganan_belum_ada(): void
    {
        $pemohon = $this->buatUserPoktan();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->actingAs($pemohon);

        $this->get('/permohonan/'.$permohonan->id)
            ->assertOk()
            ->assertSee('Belum ada kasus penanganan.')
            ->assertSee('Status penanganan muncul setelah permohonan diterima');
    }

    public function test_show_belum_ada_petugas_yang_ditugaskan(): void
    {
        $pemohon = $this->buatUserPoktan();
        $operator = $this->buatUserDenganRole('operator_uptd');

        $permohonan = PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosis($pemohon)->id,
            'kelompok_tani_id' => 1,
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
            'created_by' => $pemohon->id,
        ]);

        KasusPenanganan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'kasus_code' => 'KS-20260819-0008',
            'current_status' => KasusPenanganan::STATUS_DITERIMA,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($pemohon);

        $this->get('/permohonan/'.$permohonan->id)
            ->assertOk()
            ->assertSee('Belum ada petugas yang ditugaskan.');
    }

    public function test_show_menampilkan_petugas_popt_yang_ditugaskan(): void
    {
        $pemohon = $this->buatUserPoktan();
        $operator = $this->buatUserDenganRole('operator_uptd');
        $popt = $this->buatUserDenganRole('popt');

        [$permohonan] = $this->buatKasusTerlacak($pemohon, $operator, $popt);

        $this->actingAs($pemohon);

        $this->get('/permohonan/'.$permohonan->id)
            ->assertOk()
            ->assertSee('POPT')
            ->assertSee($popt->name)
            ->assertSee('Penugasan Aktif')
            ->assertSee('Kerjakan segera.');
    }

    public function test_show_timeline_menampilkan_peristiwa_secara_kronologis(): void
    {
        $pemohon = $this->buatUserPoktan();
        $operator = $this->buatUserDenganRole('operator_uptd');
        $popt = $this->buatUserDenganRole('popt');

        [$permohonan] = $this->buatKasusTerlacak($pemohon, $operator, $popt);

        $this->actingAs($pemohon);

        $response = $this->get('/permohonan/'.$permohonan->id)->assertOk();

        $response->assertSee('Timeline')
            ->assertSee('Permohonan Diajukan')
            ->assertSee('Permohonan Diterima')
            ->assertSee('POPT Ditugaskan')
            ->assertSee('Dalam Pelaksanaan')
            ->assertSee('Layak ditindaklanjuti.')
            ->assertSee('Mulai pelaksanaan di lapangan.')
            ->assertSee('oleh '.$popt->name)
            ->assertSee($pemohon->name);
    }

    public function test_show_timeline_menyelesaikan_dengan_status_selesai(): void
    {
        $pemohon = $this->buatUserPoktan();
        $operator = $this->buatUserDenganRole('operator_uptd');
        $popt = $this->buatUserDenganRole('popt');

        [$permohonan] = $this->buatKasusTerlacak($pemohon, $operator, $popt, KasusPenanganan::STATUS_SELESAI);

        $this->actingAs($pemohon);

        $this->get('/permohonan/'.$permohonan->id)
            ->assertOk()
            ->assertSee('Permohonan Diajukan')
            ->assertSee('Permohonan Diterima')
            ->assertSee('POPT Ditugaskan')
            ->assertSee('Dalam Pelaksanaan')
            ->assertSee('Selesai')
            ->assertSee('Penanganan selesai.');
    }

    public function test_show_bersifat_read_only_tanpa_aksi_perubahan(): void
    {
        $pemohon = $this->buatUserPoktan();
        $operator = $this->buatUserDenganRole('operator_uptd');
        $popt = $this->buatUserDenganRole('popt');

        [$permohonan] = $this->buatKasusTerlacak($pemohon, $operator, $popt);

        $this->actingAs($pemohon);

        $this->get('/permohonan/'.$permohonan->id)
            ->assertOk()
            ->assertDontSee('Ubah Status')
            ->assertDontSee('Update Status')
            ->assertDontSee('Terima Permohonan')
            ->assertDontSee('Tolak Permohonan')
            ->assertDontSee('Tugaskan POPT')
            ->assertDontSee('Hapus Riwayat')
            ->assertDontSee('Hapus');
    }
}
