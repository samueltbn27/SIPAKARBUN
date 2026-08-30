<?php

namespace Tests\Feature;

use App\Models\Gejala;
use App\Models\Penyakit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class KnowledgeImageTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

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

    public function test_popt_tidak_bisa_mutasi_foto_dan_resource_mengirim_url_foto(): void
    {
        $popt = $this->createPopt();
        $gejala = Gejala::factory()->create(['image_path' => 'knowledge/gejala/example.webp']);
        $penyakit = Penyakit::factory()->create(['image_path' => 'knowledge/penyakit/example.webp']);

        $this->actingAs($popt)->post('/knowledge/gejala', [
            'nama' => 'Tidak boleh',
            'image' => UploadedFile::fake()->image('blocked.jpg'),
        ])->assertForbidden();

        $this->actingAs($popt)->getJson('/api/gejala')->assertOk()
            ->assertJsonPath('data.0.image_path', 'knowledge/gejala/example.webp')
            ->assertJsonPath('data.0.image_url', 'http://localhost/storage/knowledge/gejala/example.webp');

        $this->actingAs($popt)->getJson('/api/penyakit')->assertOk()
            ->assertJsonFragment(['image_path' => 'knowledge/penyakit/example.webp'])
            ->assertJsonFragment(['image_url' => 'http://localhost/storage/knowledge/penyakit/example.webp']);

        $this->assertNotNull($penyakit->fresh());
    }
}
