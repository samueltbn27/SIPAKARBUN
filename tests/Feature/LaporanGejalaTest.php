<?php

namespace Tests\Feature;

use App\Contracts\KomoditasReferensiClient;
use App\Models\Gejala;
use App\Models\LaporanGejala;
use App\Models\User;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class LaporanGejalaTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'operator_uptd', 'popt', 'poktan'] as $role) {
            Role::findOrCreate($role);
        }

        $this->app->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);
        Storage::fake('public');
        Storage::fake('local');
    }

    private function createPoktan(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('poktan');

        return $user;
    }

    private function createReport(User $reporter, array $overrides = []): LaporanGejala
    {
        return LaporanGejala::create(array_merge([
            'report_code' => 'LG-'.fake()->unique()->numerify('##########'),
            'commodity_id' => 1,
            'commodity_name_snapshot' => 'Kopi Arabika',
            'description' => 'Permukaan bawah daun menunjukkan bercak baru berwarna jingga.',
            'status' => LaporanGejala::STATUS_DIAJUKAN,
            'created_by' => $reporter->id,
        ], $overrides));
    }

    public function test_poktan_dapat_melaporkan_gejala_tanpa_membuat_diagnosis(): void
    {
        $poktan = $this->createPoktan();

        $response = $this->actingAs($poktan)->postJson('/diagnosis/laporan-gejala', [
            'commodity_id' => 1,
            'description' => 'Daun muda menggulung dan muncul bercak putih yang belum pernah saya lihat.',
            'location_description' => 'Blok kebun utara, Desa Sukamaju.',
            'image' => UploadedFile::fake()->image('gejala-baru.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', LaporanGejala::STATUS_DIAJUKAN)
            ->assertJsonPath('data.commodity_name', 'Kopi Arabika');

        $report = LaporanGejala::firstOrFail();
        $this->assertDatabaseHas('laporan_gejala', [
            'id' => $report->id,
            'created_by' => $poktan->id,
            'status' => LaporanGejala::STATUS_DIAJUKAN,
        ]);
        Storage::disk('local')->assertExists($report->image_path);
        Storage::disk('public')->assertMissing($report->image_path);
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_laporan_memvalidasi_deskripsi_foto_dan_komoditas(): void
    {
        $poktan = $this->createPoktan();

        $this->actingAs($poktan)->postJson('/diagnosis/laporan-gejala', [
            'commodity_id' => 1,
            'description' => '',
            'image' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['description', 'image']);

        $this->postJson('/diagnosis/laporan-gejala', [
            'commodity_id' => 999,
            'description' => 'Daun berubah warna dan menggulung pada beberapa tanaman.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('commodity_id');
    }

    public function test_poktan_hanya_dapat_melihat_laporan_miliknya(): void
    {
        $pemilik = $this->createPoktan();
        $penggunaLain = $this->createPoktan();
        $milikPemilik = $this->createReport($pemilik);
        $milikOrangLain = $this->createReport($penggunaLain, ['report_code' => 'LG-OTHER-0001']);

        $this->actingAs($pemilik)->get('/diagnosis/laporan-gejala')
            ->assertOk()
            ->assertSee($milikPemilik->report_code)
            ->assertDontSee($milikOrangLain->report_code);

        $this->get('/diagnosis/laporan-gejala/'.$milikOrangLain->id)->assertNotFound();
        $this->get('/diagnosis/laporan-gejala/'.$milikPemilik->id)->assertOk()->assertSee($milikPemilik->description);

        $privateImagePath = 'laporan-gejala/private-bukti.jpg';
        Storage::disk('local')->put($privateImagePath, 'private image bytes');
        $milikPemilik->update(['image_path' => $privateImagePath]);
        $this->actingAs($penggunaLain)->get('/laporan-gejala/'.$milikPemilik->id.'/foto')->assertNotFound();
        $this->actingAs($pemilik)->get('/laporan-gejala/'.$milikPemilik->id.'/foto')->assertOk();
        $this->actingAs($this->createPopt())->get('/laporan-gejala/'.$milikPemilik->id.'/foto')->assertOk();
    }

    public function test_popt_dapat_meminta_informasi_tambahan_dan_poktan_dapat_menjawab(): void
    {
        $poktan = $this->createPoktan();
        $report = $this->createReport($poktan);
        $popt = $this->createPopt();

        $this->actingAs($popt)->get('/popt/laporan-gejala')
            ->assertOk()
            ->assertSee($report->report_code);

        $this->post('/popt/laporan-gejala/'.$report->id.'/review', [
            'action' => LaporanGejala::STATUS_PERLU_INFORMASI,
            'review_note' => 'Mohon tambahkan sejak kapan gejala mulai terlihat.',
        ])->assertRedirect();

        $this->assertDatabaseHas('laporan_gejala', [
            'id' => $report->id,
            'status' => LaporanGejala::STATUS_PERLU_INFORMASI,
            'review_note' => 'Mohon tambahkan sejak kapan gejala mulai terlihat.',
        ]);

        $this->actingAs($poktan)->post('/diagnosis/laporan-gejala/'.$report->id.'/informasi', [
            'additional_information' => 'Gejala mulai terlihat sekitar tiga hari lalu.',
        ])->assertRedirect();

        $this->assertDatabaseHas('laporan_gejala', [
            'id' => $report->id,
            'status' => LaporanGejala::STATUS_DIAJUKAN,
            'additional_information' => 'Gejala mulai terlihat sekitar tiga hari lalu.',
        ]);
    }

    public function test_popt_dapat_menandai_laporan_sebagai_duplikat(): void
    {
        $report = $this->createReport($this->createPoktan());

        $this->actingAs($this->createPopt())->post('/popt/laporan-gejala/'.$report->id.'/review', [
            'action' => LaporanGejala::STATUS_DUPLIKAT,
            'review_note' => 'Gejala ini sudah tercatat pada laporan LG-REF-01.',
        ])->assertRedirect();

        $this->assertDatabaseHas('laporan_gejala', [
            'id' => $report->id,
            'status' => LaporanGejala::STATUS_DUPLIKAT,
        ]);
    }

    public function test_popt_membuat_draft_dan_hanya_admin_operator_dapat_mengaktifkannya(): void
    {
        $imagePath = UploadedFile::fake()->image('bukti-gejala.jpg')->store('laporan-gejala', 'local');
        $report = $this->createReport($this->createPoktan(), ['image_path' => $imagePath]);

        $this->actingAs($this->createPopt())->post('/popt/laporan-gejala/'.$report->id.'/review', [
            'action' => 'buat_draft',
            'draft_symptom_name' => 'Daun menggulung disertai bercak putih',
        ])->assertRedirect();

        $report->refresh();
        $draft = Gejala::findOrFail($report->gejala_id);

        $this->assertSame(LaporanGejala::STATUS_DRAFT_DIBUAT, $report->status);
        $this->assertSame(Gejala::STATUS_DRAFT, $draft->status);
        $this->assertNotSame($report->image_path, $draft->image_path);
        Storage::disk('local')->assertExists($report->image_path);
        Storage::disk('public')->assertExists($draft->image_path);
        $this->assertSame(0, Gejala::aktifSaja()->count());

        $this->actingAs($this->createPopt())->post('/knowledge/publikasi/toggle', [
            'model' => 'Gejala', 'id' => $draft->id, 'status' => Gejala::STATUS_AKTIF,
        ])->assertForbidden();

        foreach ([$this->createOperator(), $this->createAdmin()] as $manager) {
            $this->actingAs($manager)->post('/knowledge/publikasi/toggle', [
                'model' => 'Gejala', 'id' => $draft->id, 'status' => Gejala::STATUS_AKTIF,
            ])->assertRedirect();

            $this->assertDatabaseHas('gejala', ['id' => $draft->id, 'status' => Gejala::STATUS_AKTIF]);
            $draft->update(['status' => Gejala::STATUS_DRAFT]);
        }
    }

    public function test_hanya_poktan_boleh_mengirim_laporan_dan_popt_yang_boleh_meninjau(): void
    {
        $report = $this->createReport($this->createPoktan());

        $this->actingAs($this->createPopt())->postJson('/diagnosis/laporan-gejala', [
            'commodity_id' => 1,
            'description' => 'Daun menguning di beberapa tanaman.',
        ])->assertForbidden();

        $this->actingAs($this->createOperator())->get('/popt/laporan-gejala')->assertForbidden();
        $this->post('/popt/laporan-gejala/'.$report->id.'/review', [
            'action' => LaporanGejala::STATUS_DUPLIKAT,
            'review_note' => 'Duplikat.',
        ])->assertForbidden();
    }
}
