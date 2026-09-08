<?php

namespace Tests\Feature;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDeleteKasusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'operator_uptd', 'popt', 'poktan', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_admin_can_archive_completed_case_and_keep_workflow_history(): void
    {
        $admin = $this->userWithRole('admin');
        $popt = $this->userWithRole('popt');
        $kasus = KasusPenanganan::factory()->create([
            'current_status' => KasusPenanganan::STATUS_SELESAI,
        ]);

        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'status' => PenugasanPopt::STATUS_SELESAI,
        ]);
        $history = RiwayatStatusPenanganan::factory()->create([
            'kasus_id' => $kasus->id,
            'status' => KasusPenanganan::STATUS_SELESAI,
            'catatan' => 'Penanganan selesai dan hasil telah dicatat.',
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/kasus/{$kasus->id}")
            ->assertOk()
            ->assertJsonPath('data.kasus_id', $kasus->id);

        $this->assertSoftDeleted('kasus_penanganan', ['id' => $kasus->id]);
        $this->assertDatabaseHas('penugasan_popt', ['id' => $assignment->id, 'kasus_id' => $kasus->id]);
        $this->assertDatabaseHas('riwayat_status_penanganan', ['id' => $history->id, 'kasus_id' => $kasus->id]);

        $this->getJson('/api/kasus')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/kasus/{$kasus->id}")->assertNotFound();
    }

    public function test_admin_cannot_archive_an_active_case(): void
    {
        $admin = $this->userWithRole('admin');

        foreach ([
            KasusPenanganan::STATUS_DITERIMA,
            KasusPenanganan::STATUS_DITUGASKAN,
            KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            KasusPenanganan::STATUS_DITUNDA,
            KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
            KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
        ] as $status) {
            $kasus = KasusPenanganan::factory()->create(['current_status' => $status]);

            Sanctum::actingAs($admin);
            $this->deleteJson("/api/kasus/{$kasus->id}")->assertForbidden();

            $this->assertDatabaseHas('kasus_penanganan', [
                'id' => $kasus->id,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_non_admin_roles_cannot_archive_completed_case(): void
    {
        foreach (['operator_uptd', 'popt', 'poktan', 'pimpinan'] as $role) {
            $kasus = KasusPenanganan::factory()->create([
                'current_status' => KasusPenanganan::STATUS_SELESAI,
            ]);

            Sanctum::actingAs($this->userWithRole($role));
            $this->deleteJson("/api/kasus/{$kasus->id}")->assertForbidden();

            $this->assertDatabaseHas('kasus_penanganan', [
                'id' => $kasus->id,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_case_resource_exposes_delete_capability_only_to_admin_for_completed_case(): void
    {
        $kasus = KasusPenanganan::factory()->create([
            'current_status' => KasusPenanganan::STATUS_SELESAI,
        ]);

        Sanctum::actingAs($this->userWithRole('admin'));
        $this->getJson('/api/kasus')->assertJsonPath('data.0.can_delete_case', true);

        Sanctum::actingAs($this->userWithRole('operator_uptd'));
        $this->getJson('/api/kasus')->assertJsonPath('data.0.can_delete_case', false);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
