<?php

namespace Tests\Feature;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PerpanjanganPenugasan;
use App\Models\User;
use App\Services\MonitoringStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperatorWorkflowPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'operator_uptd', 'popt', 'poktan', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function kasus(string $status = KasusPenanganan::STATUS_DITERIMA): KasusPenanganan
    {
        return KasusPenanganan::factory()->create(['current_status' => $status]);
    }

    private function assign(KasusPenanganan $kasus, User $operator, User $popt, Carbon $deadline): void
    {
        $this->actingAs($operator)
            ->postJson("/api/kasus/{$kasus->id}/assign-popt", [
                'popt_id' => $popt->id,
                'deadline_at' => $deadline->toDateTimeString(),
                'catatan' => 'Prioritaskan pemeriksaan blok utara.',
            ])
            ->assertOk();
    }

    public function test_assignment_requires_future_deadline_and_stores_acceptance_as_null(): void
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $kasus = $this->kasus();

        $this->actingAs($operator)
            ->postJson("/api/kasus/{$kasus->id}/assign-popt", ['popt_id' => $popt->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deadline_at']);

        $this->actingAs($operator)
            ->postJson("/api/kasus/{$kasus->id}/assign-popt", [
                'popt_id' => $popt->id,
                'deadline_at' => now()->subMinute()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deadline_at']);

        $deadline = now()->addDays(3)->startOfMinute();
        $this->assign($kasus, $operator, $popt, $deadline);

        $assignment = PenugasanPopt::query()->where('kasus_id', $kasus->id)->firstOrFail();
        $this->assertSame(PenugasanPopt::STATUS_AKTIF, $assignment->status);
        $this->assertNull($assignment->accepted_at);
        $this->assertEquals($deadline->timestamp, $assignment->deadline_at->timestamp);
    }

    public function test_reassignment_preserves_history_and_cancels_pending_extension(): void
    {
        $operator = $this->user('operator_uptd');
        $firstPopt = $this->user('popt');
        $secondPopt = $this->user('popt');
        $kasus = $this->kasus();
        $firstDeadline = now()->addDays(2)->startOfMinute();
        $secondDeadline = now()->addDays(5)->startOfMinute();

        $this->assign($kasus, $operator, $firstPopt, $firstDeadline);
        $firstAssignment = PenugasanPopt::query()->where('kasus_id', $kasus->id)->firstOrFail();
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $firstAssignment->id,
            'requested_by' => $firstPopt->id,
            'current_deadline_at' => $firstDeadline,
            'proposed_deadline_at' => now()->addDays(4),
            'reason' => 'Hujan deras.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        $this->assign($kasus->fresh(), $operator, $secondPopt, $secondDeadline);

        $firstAssignment->refresh();
        $newAssignment = PenugasanPopt::query()->where('kasus_id', $kasus->id)->where('status', PenugasanPopt::STATUS_AKTIF)->firstOrFail();
        $extension->refresh();

        $this->assertSame(PenugasanPopt::STATUS_DICABUT, $firstAssignment->status);
        $this->assertEquals($firstDeadline->timestamp, $firstAssignment->deadline_at->timestamp);
        $this->assertSame(PerpanjanganPenugasan::STATUS_CANCELLED, $extension->status);
        $this->assertSame($operator->id, $extension->reviewed_by);
        $this->assertSame($secondPopt->id, $newAssignment->popt_id);
        $this->assertEquals($secondDeadline->timestamp, $newAssignment->deadline_at->timestamp);
        $this->assertNull($newAssignment->accepted_at);
    }

    public function test_operator_sees_deadline_acceptance_overdue_indicator_and_extension_history(): void
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $kasus = $this->kasus(KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'deadline_at' => now()->subDay(),
            'accepted_at' => null,
        ]);
        PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id,
            'current_deadline_at' => now()->subDay(),
            'proposed_deadline_at' => now()->addDays(2),
            'reason' => 'Akses kebun tertutup sementara.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        $this->actingAs($operator)
            ->get(route('operator.kasus.show', $kasus->id))
            ->assertOk()
            ->assertSee('Target Penyelesaian')
            ->assertSee('Menunggu POPT menerima penugasan')
            ->assertSee('Melewati Batas Waktu')
            ->assertSee('Permintaan Perpanjangan')
            ->assertSee('Akses kebun tertutup sementara.');
    }

    public function test_operator_can_approve_extension_and_monitoring_recalculates_from_new_deadline(): void
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $kasus = $this->kasus(KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'deadline_at' => now()->subDay(),
        ]);
        $newDeadline = now()->addDays(3)->startOfMinute();
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id,
            'current_deadline_at' => now()->subDay(),
            'proposed_deadline_at' => $newDeadline,
            'reason' => 'Perlu pemeriksaan tambahan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        $this->actingAs($operator)
            ->post(route('operator.kasus.extension.approve', [$kasus->id, $extension->id]), ['review_note' => 'Disetujui.'])
            ->assertRedirect(route('operator.kasus.show', $kasus->id));

        $assignment->refresh();
        $extension->refresh();
        $resolved = app(MonitoringStatusService::class)->resolve($kasus->fresh('penugasanAktif'));

        $this->assertSame(PerpanjanganPenugasan::STATUS_APPROVED, $extension->status);
        $this->assertEquals($newDeadline->timestamp, $assignment->deadline_at->timestamp);
        $this->assertFalse($resolved['is_overdue']);
    }

    public function test_operator_must_give_reason_when_rejecting_and_cannot_review_twice(): void
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $kasus = $this->kasus(KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'deadline_at' => now()->addDay(),
        ]);
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id,
            'current_deadline_at' => now()->addDay(),
            'proposed_deadline_at' => now()->addDays(3),
            'reason' => 'Kendala lapangan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        $this->actingAs($operator)
            ->post(route('operator.kasus.extension.reject', [$kasus->id, $extension->id]), [])
            ->assertSessionHasErrors('review_note');

        $this->actingAs($operator)
            ->post(route('operator.kasus.extension.reject', [$kasus->id, $extension->id]), ['review_note' => 'Deadline masih cukup.'])
            ->assertRedirect(route('operator.kasus.show', $kasus->id));

        $this->actingAs($operator)
            ->post(route('operator.kasus.extension.approve', [$kasus->id, $extension->id]), [])
            ->assertSessionHasErrors('extension_id');

        $this->assertDatabaseHas('perpanjangan_penugasan', [
            'id' => $extension->id,
            'status' => PerpanjanganPenugasan::STATUS_REJECTED,
            'review_note' => 'Deadline masih cukup.',
        ]);
    }

    public function test_popt_poktan_and_pimpinan_cannot_review_operator_extensions(): void
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $kasus = $this->kasus(KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'deadline_at' => now()->addDay(),
        ]);
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id,
            'current_deadline_at' => now()->addDay(),
            'proposed_deadline_at' => now()->addDays(3),
            'reason' => 'Kendala lapangan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        foreach (['popt', 'poktan', 'pimpinan'] as $role) {
            $this->actingAs($this->user($role))
                ->post(route('operator.kasus.extension.approve', [$kasus->id, $extension->id]))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('perpanjangan_penugasan', [
            'id' => $extension->id,
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);
    }

    public function test_completed_case_cannot_review_pending_extension(): void
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $kasus = $this->kasus(KasusPenanganan::STATUS_SELESAI);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'status' => PenugasanPopt::STATUS_SELESAI,
            'deadline_at' => now()->addDay(),
        ]);
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id,
            'current_deadline_at' => now()->addDay(),
            'proposed_deadline_at' => now()->addDays(3),
            'reason' => 'Terlambat mengirim permintaan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        $this->actingAs($operator)
            ->post(route('operator.kasus.extension.reject', [$kasus->id, $extension->id]), ['review_note' => 'Kasus sudah selesai.'])
            ->assertSessionHasErrors('extension_id');

        $this->assertDatabaseHas('perpanjangan_penugasan', [
            'id' => $extension->id,
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);
    }
}
