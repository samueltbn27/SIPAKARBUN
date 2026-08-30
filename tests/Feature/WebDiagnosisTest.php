<?php

namespace Tests\Feature;

use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test halaman web Diagnosis Poktan (TAHAP 2).
 *
 *   GET  /diagnosis        — wizard diagnosis.
 *   POST /diagnosis        — proses & simpan diagnosis (redirect ke hasil).
 *   GET  /diagnosis/history— riwayat diagnosis milik user.
 *   GET  /diagnosis/{id}   — detail hasil diagnosis milik user.
 *
 * Knowledge API M1 disimulasikan via Http::fake; komoditas memakai
 * MockKomoditasReferensiClient (id 1 = Kopi Arabika).
 */
class WebDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://knowledge.test';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'popt', 'operator_uptd', 'poktan'] as $role) {
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
                [
                    'id' => 2,
                    'kode' => 'PY-002',
                    'nama' => 'Layu Bakteri',
                    'deskripsi' => null,
                    'komoditas_id' => [1],
                    'aturan_cf' => [
                        ['gejala_id' => 3, 'gejala_nama' => 'Batang layu', 'cf_pakar' => 0.8],
                    ],
                    'solusi' => [
                        ['judul' => 'Cabut tanaman', 'deskripsi' => 'Cabut dan bakar.'],
                    ],
                    'updated_at' => '2026-08-12T10:00:00+00:00',
                ],
            ]], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ['id' => 2, 'kode' => 'GJ-002', 'nama' => 'Daun menguning', 'deskripsi' => null],
                ['id' => 3, 'kode' => 'GJ-003', 'nama' => 'Batang layu', 'deskripsi' => null],
            ]], 200),
        ]);
    }

    private function buatPoktan(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('poktan');

        return $user;
    }

    private function loginSebagai(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_tamu_tidak_bisa_membuka_halaman_diagnosis(): void
    {
        $this->get('/diagnosis')->assertRedirect(route('login'));
        $this->get('/diagnosis/history')->assertRedirect(route('login'));

        $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->assertRedirect(route('login'));
    }

    public function test_role_non_poktan_diblokir_dari_diagnosis(): void
    {
        foreach (['operator_uptd', 'popt', 'admin'] as $role) {
            $this->loginSebagai($role);

            $this->get('/diagnosis')->assertForbidden();
            $this->get('/diagnosis/history')->assertForbidden();
            $this->get('/diagnosis/1')->assertForbidden();
            $this->post('/diagnosis', [
                'commodity_id' => 1,
                'symptom_ids' => [1],
            ])->assertForbidden();

            auth()->logout();
        }
    }

    public function test_wizard_menampilkan_komoditas_gejala_dan_tingkat_keyakinan(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $response = $this->get('/diagnosis')->assertOk();

        $response->assertSee('Kopi Arabika')
            ->assertSee('Bercak jingga')
            ->assertSee('Batang layu')
            ->assertSee('Foto belum tersedia')
            ->assertSee('Tidak Yakin')
            ->assertSee('Sangat Yakin');
    }

    public function test_wizard_memiliki_state_loading_dan_empty_gejala(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $response = $this->get('/diagnosis')->assertOk();

        // Loading saat komoditas dipilih / gejala dimuat dari basis pengetahuan.
        $response->assertSee('Memuat gejala untuk komoditas ini…');
        // Empty state saat komoditas tidak memiliki gejala.
        $response->assertSee('Tidak ada gejala untuk komoditas ini');
    }

    public function test_wizard_validasi_wajib_komoditas_dan_gejala(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        // Komoditas wajib dipilih (validasi server-side).
        $this->post('/diagnosis', ['symptom_ids' => [1]])->assertSessionHasErrors('commodity_id');
        // Minimal satu gejala wajib dipilih.
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => []])->assertSessionHasErrors('symptom_ids');
        // Tingkat keyakinan harus dalam rentang 0–1.
        $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
            'symptom_confidence' => [1 => 1.4],
        ])->assertSessionHasErrors('symptom_confidence.1');
    }

    public function test_wizard_menampilkan_error_saat_knowledge_tidak_tersedia(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response([], 503),
            self::BASE_URL.'/api/gejala*' => Http::response([], 503),
        ]);
        $this->loginSebagai('poktan');

        $response = $this->get('/diagnosis')->assertOk();

        $response->assertSee('Data knowledge tidak dapat dimuat. Silakan coba kembali.')
            ->assertDontSee('Sangat Yakin');
    }

    public function test_store_berhasil_redirect_ke_hasil(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $response = $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2, 3],
        ]);

        $this->assertDatabaseCount('diagnoses', 1);
        $this->assertDatabaseHas('diagnoses', ['user_id' => $user->id, 'status' => 'selesai']);

        $diagnosis = Diagnosis::first();

        $response->assertRedirect(route('diagnosis.show', ['id' => $diagnosis->id]))
            ->assertSessionHas('success');
    }

    public function test_store_dengan_confidence_menghasilkan_trace(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2, 3],
            'symptom_confidence' => [1 => 0.5, 2 => 0.5, 3 => 0.5],
        ])->assertRedirect();

        $result = Diagnosis::first()->results()->where('ranking', 1)->first();

        $this->assertSame(0.643, (float) $result->cf_value);
        $this->assertSame(0.5, (float) $result->trace_snapshot[0]['cf_user']);
        $this->assertSame(0.9, (float) $result->trace_snapshot[0]['cf_pakar']);
    }

    public function test_store_validasi_menolak_payload_tanpa_gejala(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->post('/diagnosis', ['commodity_id' => 1])
            ->assertSessionHasErrors(['symptom_ids'])
            ->assertSessionHasInput(['commodity_id' => 1]);
    }

    public function test_store_validasi_menolak_komoditas_tidak_ada(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->post('/diagnosis', [
            'commodity_id' => 999,
            'symptom_ids' => [1],
        ])->assertSessionHasErrors(['commodity_id']);
    }

    public function test_store_validasi_menolak_confidence_di_luar_rentang(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
            'symptom_confidence' => [1 => 1.5],
        ])->assertSessionHasErrors(['symptom_confidence.1']);
    }

    public function test_store_kembali_ke_wizard_saat_knowledge_gagal(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response([], 500),
            self::BASE_URL.'/api/gejala*' => Http::response([], 500),
        ]);
        $user = $this->loginSebagai('poktan');

        // Buka wizard dulu agar `back()` kembali ke /diagnosis.
        $this->get('/diagnosis')->assertOk();

        $response = $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2, 3],
        ]);

        $this->assertDatabaseCount('diagnoses', 0);

        $response->assertRedirect(route('diagnosis.index'))
            ->assertSessionHasErrors(['symptom_ids']);
        $this->assertNull(Diagnosis::first());
    }

    public function test_store_kembali_ke_wizard_saat_tidak_ada_penyakit_cocok(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response(['data' => []], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
            ]], 200),
        ]);
        $this->loginSebagai('poktan');

        // Buka wizard dulu agar `back()` kembali ke /diagnosis.
        $this->get('/diagnosis')->assertOk();

        $this->post('/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->assertRedirect(route('diagnosis.index'))
            ->assertSessionHas('error', 'Tidak ada penyakit yang cocok dengan gejala yang dipilih.');

        // Tidak boleh ada diagnosis yatim (tanpa hasil) yang tercatat.
        $this->assertDatabaseCount('diagnoses', 0);
        $this->assertDatabaseCount('diagnosis_results', 0);
    }

    public function test_histori_menampilkan_empty_state_awal(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->get('/diagnosis/history')
            ->assertOk()
            ->assertSee('Belum ada riwayat diagnosis');
    }

    public function test_histori_menampilkan_diagnosis_user_sendiri(): void
    {
        $this->fakeKnowledge();
        $userA = $this->loginSebagai('poktan');
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]]);

        $userB = User::factory()->create(['is_active' => true]);
        $userB->assignRole('poktan');
        $userBDiagId = Diagnosis::create(['user_id' => $userB->id, 'commodity_id' => 1, 'status' => 'selesai'])->id;

        auth()->logout();
        $this->actingAs($userA);

        $response = $this->get('/diagnosis/history')->assertOk();

        $response->assertSee('Kopi Arabika')
            ->assertSee('Karat Daun Kopi')
            ->assertDontSee('/diagnosis/'.$userBDiagId);
    }

    public function test_histori_dan_show_hanya_user_pemilik(): void
    {
        $this->fakeKnowledge();
        $owner = $this->loginSebagai('poktan');
        $ownerId = $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1, 2, 3]])
            ->headers->get('Location');

        $intruder = $this->loginSebagai('poktan');

        $this->get('/diagnosis/history')->assertDontSee('Karat Daun Kopi');

        preg_match('#/diagnosis/(\d+)#', $ownerId, $m);

        $this->get('/diagnosis/'.$m[1])->assertNotFound();
    }

    public function test_show_404_untuk_id_tidak_ada(): void
    {
        $this->loginSebagai('poktan');

        $this->get('/diagnosis/99999')->assertNotFound();
    }

    public function test_kode_diagnosis_tergenerate_saat_store(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1, 2, 3]])->assertRedirect();

        $diagnosis = Diagnosis::first();

        $this->assertNotNull($diagnosis->kode);
        $this->assertMatchesRegularExpression('/^DG-\d{8}-\d{4}$/', $diagnosis->kode);
    }

    public function test_histori_menampilkan_kode_diagnosis(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]]);

        $kode = Diagnosis::first()->kode;

        $this->get('/diagnosis/history')->assertOk()->assertSee($kode);
    }

    public function test_histori_search_berdasarkan_kode_dan_penyakit(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');
        $a = $this->buatDiagnosisLangsung($user, 'Karat Daun Kopi', kode: 'DG-TEST-AA');
        $this->buatDiagnosisLangsung($user, 'Layu Bakteri', kode: 'DG-TEST-BB');

        // Cari berdasarkan kode.
        $this->get('/diagnosis/history?q=TEST-AA')->assertOk()
            ->assertSee($a->kode)
            ->assertDontSee('DG-TEST-BB');

        // Cari berdasarkan nama penyakit utama.
        $this->get('/diagnosis/history?q=Layu')->assertOk()
            ->assertSee('Layu Bakteri')
            ->assertDontSee('Karat Daun Kopi');
    }

    public function test_histori_filter_komoditas(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');
        $this->buatDiagnosisLangsung($user, 'Karat Daun Kopi', komoditas: 1);
        $this->buatDiagnosisLangsung($user, 'Layu Bakteri', komoditas: 2);

        $this->get('/diagnosis/history?komoditas=2')->assertOk()
            ->assertSee('Layu Bakteri')
            ->assertDontSee('Karat Daun Kopi');
    }

    public function test_histori_filter_tanggal(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');
        $kemarin = now()->subDay()->toDateString();

        $this->buatDiagnosisLangsung($user, 'Karat Daun Kopi', tanggal: now()->toDateString());
        $this->buatDiagnosisLangsung($user, 'Layu Bakteri', tanggal: $kemarin);

        $this->get('/diagnosis/history?tanggal='.$kemarin)->assertOk()
            ->assertSee('Layu Bakteri')
            ->assertDontSee('Karat Daun Kopi');
    }

    public function test_histori_sorting_nilai_cf(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');
        $this->buatDiagnosisLangsung($user, 'Karat Daun Kopi', cf: 0.5);
        $this->buatDiagnosisLangsung($user, 'Layu Bakteri', cf: 0.9);

        $this->get('/diagnosis/history?sort=cf')->assertOk()
            ->assertSeeInOrder(['Layu Bakteri', 'Karat Daun Kopi']);
    }

    public function test_histori_empty_state_saat_filter_tidak_cocok(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->get('/diagnosis/history?q=tidak-ada')
            ->assertOk()
            ->assertSee('Tidak ada hasil yang cocok')
            ->assertSee('Reset Filter');
    }

    public function test_histori_error_state_saat_referensi_komoditas_gagal(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]]);

        $this->app->instance(KomoditasReferensiClient::class, new class implements KomoditasReferensiClient
        {
            public function all(): array
            {
                throw new \RuntimeException('referensi turun');
            }

            public function find(int $id): ?array
            {
                throw new \RuntimeException('referensi turun');
            }
        });

        $this->get('/diagnosis/history')->assertOk()
            ->assertSee('Referensi komoditas sedang tidak dapat dimuat.');
    }

    public function test_show_menampilkan_kode_dan_info_snapshot(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1, 2, 3]]);
        $diagnosis = Diagnosis::first();

        $this->get('/diagnosis/'.$diagnosis->id)->assertOk()
            ->assertSee($diagnosis->kode)
            ->assertSee('Informasi Snapshot');
    }

    // ==== TAHAP 3 — Halaman Hasil Diagnosis Poktan ====

    public function test_show_memiliki_state_loading(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = $this->buatDiagnosisLangsung($user);

        $this->get('/diagnosis/'.$diagnosis->id)->assertOk()
            ->assertSee('Memuat hasil diagnosis');
    }

    public function test_show_menampilkan_semua_informasi_hasil_diagnosis(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = Diagnosis::create([
            'user_id' => $user->id,
            'commodity_id' => 1,
            'kode' => 'DG-20260819-0001',
            'status' => 'selesai',
        ]);

        $diagnosis->symptoms()->createMany([
            ['symptom_id' => 1, 'symptom_name_snapshot' => 'Bercak jingga', 'cf_user' => 1.0],
            ['symptom_id' => 2, 'symptom_name_snapshot' => 'Daun menguning', 'cf_user' => 0.8],
        ]);

        DiagnosisResult::create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'disease_image_url' => 'http://localhost/storage/knowledge/penyakit/karat.webp',
            'solution_snapshot' => [
                ['judul' => 'Pangkas bagian tanaman yang terinfeksi', 'deskripsi' => 'Buang daun terinfeksi.'],
                ['judul' => 'Lakukan penanganan sesuai rekomendasi', 'deskripsi' => null],
            ],
            'trace_snapshot' => [],
            'cf_value' => 0.82,
            'ranking' => 1,
        ]);

        $response = $this->get('/diagnosis/'.$diagnosis->id)->assertOk();

        $response->assertSee('DG-20260819-0001')                       // kode diagnosis
            ->assertSee('Tanggal Diagnosis')                          // tanggal diagnosis
            ->assertSee('Kopi Arabika')                               // komoditas
            ->assertSee('Karat Daun Kopi')                            // penyakit utama
            ->assertSee('0,82')                                       // nilai CF
            ->assertSee('82%')                                        // nilai CF (persen)
            ->assertSee('Bercak jingga')                              // gejala yang dipilih
            ->assertSee('Daun menguning')                             // gejala yang dipilih
            ->assertSee('Sangat Yakin')                               // tingkat keyakinan cf 1.0
            ->assertSee('Pangkas bagian tanaman yang terinfeksi')     // solusi/rekomendasi
            ->assertSee('Lakukan penanganan sesuai rekomendasi');     // solusi/rekomendasi
    }

    public function test_show_menampilkan_kandidat_penyakit(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = Diagnosis::create([
            'user_id' => $user->id,
            'commodity_id' => 1,
            'kode' => 'DG-20260819-0002',
            'status' => 'selesai',
        ]);

        DiagnosisResult::create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'disease_image_url' => 'http://localhost/storage/knowledge/penyakit/karat.webp',
            'solution_snapshot' => [],
            'trace_snapshot' => [],
            'cf_value' => 0.82,
            'ranking' => 1,
        ]);

        DiagnosisResult::create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 2,
            'disease_name_snapshot' => 'Layu Bakteri',
            'solution_snapshot' => [],
            'trace_snapshot' => [],
            'cf_value' => 0.40,
            'ranking' => 2,
        ]);

        $this->get('/diagnosis/'.$diagnosis->id)->assertOk()
            ->assertSee('Ranking Kandidat')
            ->assertSee('Peringkat #1 dari 2 kandidat')
            ->assertSee('Karat Daun Kopi')
            ->assertSee('http://localhost/storage/knowledge/penyakit/karat.webp')
            ->assertSee('Layu Bakteri');
    }

    public function test_show_empty_state_saat_tidak_ada_penyakit_terdeteksi(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = Diagnosis::create([
            'user_id' => $user->id,
            'commodity_id' => 1,
            'kode' => 'DG-EMPTY-0001',
            'status' => 'selesai',
        ]);

        $this->get('/diagnosis/'.$diagnosis->id)->assertOk()
            ->assertSee('Tidak ada penyakit yang cocok')
            ->assertSee('Diagnosis Ulang')
            ->assertDontSee('Ajukan Penanganan');
    }

    public function test_show_error_state_saat_referensi_komoditas_gagal(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = $this->buatDiagnosisLangsung($user);

        $this->app->instance(KomoditasReferensiClient::class, new class implements KomoditasReferensiClient
        {
            public function all(): array
            {
                throw new \RuntimeException('referensi turun');
            }

            public function find(int $id): ?array
            {
                throw new \RuntimeException('referensi turun');
            }
        });

        $this->get('/diagnosis/'.$diagnosis->id)->assertOk()
            ->assertSee('Referensi komoditas sedang tidak dapat dimuat.')
            ->assertSee('Komoditas #'.$diagnosis->commodity_id)
            ->assertSee('Karat Daun Kopi');
    }

    public function test_show_menyediakan_tombol_kembali_dan_ajukan_penanganan(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = $this->buatDiagnosisLangsung($user);

        $response = $this->get('/diagnosis/'.$diagnosis->id)->assertOk();

        $response->assertSee('Ajukan Penanganan')
            ->assertSee(route('permohonan.create', ['diagnosis_id' => $diagnosis->id]))
            ->assertSee('Kembali')
            ->assertSee(route('diagnosis.history'));
    }

    public function test_show_tombol_ajukan_penanganan_nonaktif_saat_diagnosis_belum_selesai(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = Diagnosis::create([
            'user_id' => $user->id,
            'commodity_id' => 1,
            'kode' => 'DG-DRAFT-0001',
            'status' => 'draft',
        ]);

        DiagnosisResult::create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'solution_snapshot' => [],
            'trace_snapshot' => [],
            'cf_value' => 0.82,
            'ranking' => 1,
        ]);

        $response = $this->get('/diagnosis/'.$diagnosis->id)->assertOk();

        $response->assertSee('Ajukan Penanganan')
            ->assertDontSee(route('permohonan.create', ['diagnosis_id' => $diagnosis->id]));
    }

    // ==== TAHAP 4 — Riwayat Diagnosis Poktan ====

    public function test_histori_tabel_menampilkan_kolom_sesuai_spek(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');
        $this->buatDiagnosisLangsung($user);

        $response = $this->get('/diagnosis/history')->assertOk();

        $response->assertSee('Kode Diagnosis')
            ->assertSee('Tanggal')
            ->assertSee('Komoditas')
            ->assertSee('Diagnosis/Penyakit')
            ->assertSee('Nilai CF')
            ->assertSee('Aksi')
            ->assertDontSee('Status');
    }

    public function test_histori_aksi_detail_menuju_halaman_hasil(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        $diagnosis = $this->buatDiagnosisLangsung($user);

        $this->get('/diagnosis/history')->assertOk()
            ->assertSee('Detail')
            ->assertSee(route('diagnosis.show', $diagnosis->id));
    }

    public function test_histori_memiliki_state_loading_saat_filter_dikirim(): void
    {
        $this->fakeKnowledge();
        $this->loginSebagai('poktan');

        $this->get('/diagnosis/history')->assertOk()
            ->assertSee('Memuat…');
    }

    public function test_histori_pagination(): void
    {
        $this->fakeKnowledge();
        $user = $this->loginSebagai('poktan');

        for ($i = 1; $i <= 16; $i++) {
            $this->buatDiagnosisLangsung($user);
        }

        $response = $this->get('/diagnosis/history')->assertOk();

        $response->assertSee('Menampilkan 1–15 dari 16 diagnosis')
            ->assertSee('page=2');
    }

    private function buatDiagnosisLangsung(
        User $user,
        string $penyakit = 'Karat Daun Kopi',
        float $cf = 0.9,
        int $komoditas = 1,
        string $tanggal = '',
        string $kode = '',
    ): Diagnosis {
        $diagnosis = Diagnosis::create([
            'user_id' => $user->id,
            'commodity_id' => $komoditas,
            'kode' => $kode !== '' ? $kode : 'DG-TEST-'.uniqid(),
            'status' => 'selesai',
        ]);

        if ($tanggal !== '') {
            $diagnosis->created_at = $tanggal;
            $diagnosis->save();
        }

        DiagnosisResult::create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => $penyakit,
            'cf_value' => $cf,
            'ranking' => 1,
            'solution_snapshot' => [],
            'trace_snapshot' => [],
        ]);

        return $diagnosis;
    }
}
