<?php

namespace Tests\Feature;

use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\RefKomoditas;
use App\Services\KnowledgeImageService;
use App\Support\PublicStorageUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class KnowledgeImageTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_public_storage_url_uses_request_origin_instead_of_app_url(): void
    {
        config(['app.url' => 'http://localhost']);
        $request = Request::create('http://127.0.0.1:8000/knowledge/gejala');

        $this->assertSame(
            'http://127.0.0.1:8000/storage/knowledge/gejala/example.webp',
            PublicStorageUrl::make('knowledge/gejala/example.webp', $request),
        );
    }

    public function test_image_service_returns_null_without_file(): void
    {
        Storage::fake('public');
        $service = app(KnowledgeImageService::class);

        $this->assertNull($service->store(null, 'gejala'));
        Storage::disk('public')->assertDirectoryEmpty('knowledge/gejala');
    }

    public function test_admin_dan_operator_bisa_upload_update_dan_hapus_foto_knowledge(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $gejala = Gejala::factory()->create();

        $this->actingAs($admin)->post('/knowledge/gejala', [
            'kode' => 'G-IMG-001',
            'nama' => 'Gejala dengan foto',
            'status' => 'aktif',
            'image' => UploadedFile::fake()->image('gejala-a.jpg'),
        ])->assertRedirect();

        $created = Gejala::where('kode', 'G-IMG-001')->firstOrFail();
        Storage::disk('public')->assertExists($created->image_path);
        $oldPath = $created->image_path;

        $this->actingAs($admin)->get(route('knowledge.gejala.show', $created))
            ->assertOk()
            ->assertSee(PublicStorageUrl::make($created->image_path));

        $this->actingAs($this->createOperator())->put('/knowledge/gejala/'.$created->id, [
            'nama' => 'Gejala dengan foto baru',
            'status' => 'aktif',
            'image' => UploadedFile::fake()->image('gejala-b.png'),
        ])->assertRedirect();

        $created->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($created->image_path);

        $this->actingAs($admin)->delete('/knowledge/gejala/'.$created->id)->assertRedirect();
        Storage::disk('public')->assertMissing($created->image_path);
        $this->assertDatabaseMissing('gejala', ['id' => $created->id]);
        $this->assertNotNull($gejala->fresh());
    }

    public function test_popt_dapat_mengusulkan_draft_dan_resource_mengirim_url_foto(): void
    {
        $popt = $this->createPopt();
        $gejala = Gejala::factory()->create(['image_path' => 'knowledge/gejala/example.webp']);
        $penyakit = Penyakit::factory()->create(['image_path' => 'knowledge/penyakit/example.webp']);

        $this->actingAs($popt)->post('/knowledge/gejala', [
            'nama' => 'Draft kontributor',
            'image' => UploadedFile::fake()->image('blocked.jpg'),
        ])->assertRedirect();
        $this->assertDatabaseHas('gejala', [
            'nama' => 'Draft kontributor',
            'status' => 'draft',
        ]);

        $baseUrl = rtrim((string) config('app.url'), '/');

        $this->actingAs($popt)->getJson('/api/gejala')->assertOk()
            ->assertJsonPath('data.0.image_path', 'knowledge/gejala/example.webp')
            ->assertJsonPath('data.0.image_url', $baseUrl.'/storage/knowledge/gejala/example.webp');

        $this->actingAs($popt)->getJson('http://127.0.0.1:8000/api/penyakit')->assertOk()
            ->assertJsonPath('data.0.image_url', 'http://127.0.0.1:8000/storage/knowledge/penyakit/example.webp');

        $this->assertNotNull($penyakit->fresh());
    }

    public function test_form_penyakit_menyimpan_dan_menampilkan_foto(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $komoditas = RefKomoditas::create([
            'nama' => 'Kopi Robusta',
            'kode' => 'KOP-IMG',
            'source' => RefKomoditas::SOURCE_DISBUN,
            'disbun_record_id' => 99001,
            'is_verified' => true,
            'sync_status' => RefKomoditas::SYNC_SYNCED,
        ]);

        $this->actingAs($admin)->post('/knowledge/penyakit', [
            'kode' => 'PNY-IMG-001',
            'nama' => 'Penyakit Uji Foto',
            'deskripsi' => 'Deskripsi uji foto',
            'status' => 'aktif',
            'komoditas_id' => [$komoditas->id],
            'image' => UploadedFile::fake()->image('penyakit.jpg'),
        ])->assertRedirect();

        $created = Penyakit::where('kode', 'PNY-IMG-001')->firstOrFail();
        Storage::disk('public')->assertExists($created->image_path);

        $this->actingAs($admin)
            ->get('/knowledge/penyakit')
            ->assertOk()
            ->assertSee($created->image_path);
    }

    public function test_request_penyakit_menampilkan_foto_tersimpan_pada_form_edit(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $komoditas = RefKomoditas::create([
            'nama' => 'Kopi Robusta',
            'kode' => 'KOP-IMG-EDIT',
            'source' => RefKomoditas::SOURCE_DISBUN,
            'disbun_record_id' => 99002,
            'is_verified' => true,
            'sync_status' => RefKomoditas::SYNC_SYNCED,
        ]);
        $penyakit = Penyakit::factory()->create(['image_path' => 'knowledge/penyakit/current.webp']);

        $this->actingAs($admin)->get('/knowledge/penyakit/'.$penyakit->id.'/edit')
            ->assertOk()
            ->assertSee('Foto Penyakit')
            ->assertSee('storage/knowledge/penyakit/current.webp');
    }

    public function test_image_url_mengikuti_origin_request_dan_port_aplikasi(): void
    {
        $popt = $this->createPopt();
        Gejala::factory()->create([
            'image_path' => 'knowledge/gejala/bpkc-cengkeh.svg',
            'nama' => 'Daun berguguran secara mendadak',
        ]);

        $this->actingAs($popt)
            ->getJson('http://127.0.0.1:8000/api/gejala')
            ->assertOk()
            ->assertJsonPath('data.0.image_url', 'http://127.0.0.1:8000/storage/knowledge/gejala/bpkc-cengkeh.svg');
    }
}
