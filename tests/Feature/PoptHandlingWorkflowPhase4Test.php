<?php

namespace Tests\Feature;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PerpanjanganPenugasan;
use App\Models\User;
use App\Services\MonitoringStatusService;
use App\Services\PoptHandlingService;
use App\Services\StatusTransitionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PoptHandlingWorkflowPhase4Test extends TestCase
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

    /** @return array{0: KasusPenanganan, 1: PenugasanPopt} */
    private function assignment(User $popt, string $status = KasusPenanganan::STATUS_DITUGASKAN, ?Carbon $deadline = null): array
    {
        $operator = $this->user('operator_uptd');
        $kasus = KasusPenanganan::factory()->create(['current_status' => $status]);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $popt->id,
            'assigned_by' => $operator->id,
            'deadline_at' => $deadline ?? now()->addDays(3),
            'accepted_at' => null,
        ]);

        return [$kasus, $assignment];
    }

    private function accept(User $popt, KasusPenanganan $kasus): void
    {
        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/accept")->assertOk();
    }

    public function test_popt_accepts_owned_assignment_once_and_enters_handling_state(): void
    {
        $popt = $this->user('popt');
        [$kasus, $assignment] = $this->assignment($popt);

        $this->accept($popt, $kasus);

        $assignment->refresh();
        $kasus->refresh();
        $this->assertNotNull($assignment->accepted_at);
        $acceptedAt = $assignment->accepted_at->timestamp;
        $this->assertSame(KasusPenanganan::STATUS_SEDANG_DIREVIEW, $kasus->current_status);
        $this->assertSame('Dalam Penanganan', app(MonitoringStatusService::class)->resolve($kasus->fresh('penugasanAktif'))['label']);

        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/accept")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accepted_at']);

        $this->assertSame($acceptedAt, $assignment->fresh()->accepted_at->timestamp);
    }

    public function test_other_popt_cannot_accept_or_mutate_owned_assignment(): void
    {
        $owner = $this->user('popt');
        $other = $this->user('popt');
        [$kasus] = $this->assignment($owner);

        Sanctum::actingAs($other);
        $this->postJson("/api/popt/kasus/{$kasus->id}/accept")->assertForbidden();
        $this->postJson("/api/popt/kasus/{$kasus->id}/progress", ['catatan' => 'Tidak berwenang.'])->assertForbidden();
        $this->postJson("/api/popt/kasus/{$kasus->id}/perpanjangan", [
            'proposed_deadline_at' => now()->addDays(5)->toDateTimeString(),
            'reason' => 'Tidak berwenang.',
        ])->assertForbidden();
    }

    public function test_non_popt_roles_cannot_use_popt_action_endpoints(): void
    {
        $owner = $this->user('popt');
        [$kasus] = $this->assignment($owner);

        foreach (['operator_uptd', 'poktan', 'pimpinan'] as $role) {
            Sanctum::actingAs($this->user($role));
            $this->postJson("/api/popt/kasus/{$kasus->id}/accept")->assertForbidden();
            $this->postJson("/api/popt/kasus/{$kasus->id}/progress", ['catatan' => 'Tidak berwenang.'])->assertForbidden();
            $this->postJson("/api/popt/kasus/{$kasus->id}/selesaikan")->assertForbidden();
        }
    }

    public function test_progress_is_append_only_and_requires_acceptance(): void
    {
        $popt = $this->user('popt');
        [$kasus] = $this->assignment($popt);
        Sanctum::actingAs($popt);

        $this->postJson("/api/popt/kasus/{$kasus->id}/progress", ['catatan' => 'Terlalu cepat.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accepted_at']);

        $this->accept($popt, $kasus);
        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/progress", ['catatan' => 'Pemeriksaan kondisi tanaman di lapangan.'])->assertCreated();
        $this->postJson("/api/popt/kasus/{$kasus->id}/progress", ['catatan' => 'Tindakan pengendalian telah dilakukan.'])->assertCreated();

        $this->assertDatabaseCount('progres_penanganan', 2);
        $this->assertDatabaseHas('progres_penanganan', ['kasus_id' => $kasus->id, 'actor_id' => $popt->id, 'catatan' => 'Pemeriksaan kondisi tanaman di lapangan.']);
        $this->assertDatabaseHas('progres_penanganan', ['kasus_id' => $kasus->id, 'actor_id' => $popt->id, 'catatan' => 'Tindakan pengendalian telah dilakukan.']);
    }

    public function test_extension_request_keeps_deadline_until_operator_approval_and_allows_overdue_request(): void
    {
        $popt = $this->user('popt');
        $deadline = now()->subDay()->startOfMinute();
        [$kasus, $assignment] = $this->assignment($popt, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, $deadline);
        $this->accept($popt, $kasus);
        $newDeadline = now()->addDays(4)->startOfMinute();

        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/perpanjangan", [
            'proposed_deadline_at' => $newDeadline->toDateTimeString(),
            'reason' => 'Pemeriksaan tambahan membutuhkan waktu.',
        ])->assertCreated();

        $extension = PerpanjanganPenugasan::firstOrFail();
        $this->assertSame(PerpanjanganPenugasan::STATUS_PENDING, $extension->status);
        $this->assertEquals($deadline->timestamp, $extension->current_deadline_at->timestamp);
        $this->assertEquals($newDeadline->timestamp, $extension->proposed_deadline_at->timestamp);
        $this->assertEquals($deadline->timestamp, $assignment->fresh()->deadline_at->timestamp);
        $this->assertTrue(app(MonitoringStatusService::class)->resolve($kasus->fresh('penugasanAktif'))['is_overdue']);

        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/perpanjangan", [
            'proposed_deadline_at' => now()->addDays(5)->toDateTimeString(),
            'reason' => 'Permintaan kedua.',
        ])->assertUnprocessable()->assertJsonValidationErrors(['extension']);
    }

    public function test_legacy_assignment_without_deadline_cannot_request_extension(): void
    {
        $popt = $this->user('popt');
        [$kasus, $assignment] = $this->assignment($popt, KasusPenanganan::STATUS_DITUGASKAN, null);
        $assignment->update(['deadline_at' => null]);
        $this->accept($popt, $kasus);

        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/perpanjangan", [
            'proposed_deadline_at' => now()->addDays(5)->toDateTimeString(),
            'reason' => 'Butuh waktu tambahan.',
        ])->assertUnprocessable()->assertJsonValidationErrors(['proposed_deadline_at']);
    }

    public function test_completion_requires_all_text_and_one_valid_photo(): void
    {
        Storage::fake('public');
        $popt = $this->user('popt');
        [$kasus] = $this->assignment($popt, KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $this->accept($popt, $kasus);
        Sanctum::actingAs($popt);

        $this->post("/api/popt/kasus/{$kasus->id}/selesaikan", [
            'ringkasan_tindakan' => 'Lengkap.',
            'hasil_penanganan' => 'Lengkap.',
            'rekomendasi' => 'Lengkap.',
        ])->assertRedirect()->assertSessionHasErrors(['photos']);

        $this->assertDatabaseCount('laporan_akhir_penanganan', 0);
        $this->assertSame(KasusPenanganan::STATUS_DALAM_PELAKSANAAN, $kasus->fresh()->current_status);

        $this->post("/api/popt/kasus/{$kasus->id}/selesaikan", [
            'ringkasan_tindakan' => 'Lengkap.',
            'hasil_penanganan' => 'Lengkap.',
            'rekomendasi' => 'Lengkap.',
            'photos' => [UploadedFile::fake()->create('bukti.svg', 10, 'image/svg+xml')],
        ])->assertRedirect()->assertSessionHasErrors('photos.0');
    }

    public function test_completion_creates_report_closes_case_and_cancels_pending_extension(): void
    {
        Storage::fake('public');
        $popt = $this->user('popt');
        [$kasus, $assignment] = $this->assignment($popt, KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $this->accept($popt, $kasus);
        $assignment->refresh();
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id,
            'current_deadline_at' => $assignment->deadline_at,
            'proposed_deadline_at' => now()->addDays(5),
            'reason' => 'Menunggu akses lahan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        Sanctum::actingAs($popt);
        $response = $this->post("/api/popt/kasus/{$kasus->id}/selesaikan", [
            'ringkasan_tindakan' => 'Pemeriksaan dan tindakan pengendalian dilakukan.',
            'hasil_penanganan' => 'Kondisi tanaman membaik.',
            'rekomendasi' => 'Pantau tanaman selama dua minggu.',
            'catatan_tambahan' => 'Dokumentasi diambil setelah tindakan.',
            'photos' => [UploadedFile::fake()->image('laporan akhir.jpg')],
        ]);

        $response->assertOk();
        $case = $kasus->fresh();
        $report = $case->finalReport()->with('evidences')->firstOrFail();
        $evidence = $report->evidences->firstOrFail();

        $this->assertSame(KasusPenanganan::STATUS_SELESAI, $case->current_status);
        $this->assertNotNull($case->completed_at);
        $this->assertSame(PenugasanPopt::STATUS_SELESAI, $assignment->fresh()->status);
        $this->assertSame(PerpanjanganPenugasan::STATUS_CANCELLED, $extension->fresh()->status);
        $this->assertSame($popt->id, $report->submitted_by);
        $this->assertSame('Pemeriksaan dan tindakan pengendalian dilakukan.', $report->ringkasan_tindakan);
        $this->assertStringStartsWith('laporan-akhir/', $evidence->file_path);
        Storage::disk('public')->assertExists($evidence->file_path);

        $this->actingAs($popt)
            ->get(route('popt.penugasan.show', $kasus->id))
            ->assertOk()
            ->assertSee('Laporan akhir sudah tersimpan dan bersifat read-only.')
            ->assertDontSee('Submit Laporan &amp; Selesaikan')
            ->assertDontSee('Tambah Progress');

        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$kasus->id}/progress", ['catatan' => 'Tidak boleh setelah selesai.'])->assertForbidden();
        $this->postJson("/api/popt/kasus/{$kasus->id}/perpanjangan", [
            'proposed_deadline_at' => now()->addDays(8)->toDateTimeString(),
            'reason' => 'Tidak boleh setelah selesai.',
        ])->assertForbidden();
    }

    public function test_generic_completion_status_is_blocked_and_legacy_completed_without_report_is_readable(): void
    {
        $popt = $this->user('popt');
        [$kasus] = $this->assignment($popt);
        Sanctum::actingAs($popt);

        $this->postJson("/api/popt/kasus/{$kasus->id}/status", ['status' => KasusPenanganan::STATUS_SELESAI])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $kasus->update(['current_status' => KasusPenanganan::STATUS_SELESAI]);
        $this->actingAs($popt)
            ->get(route('popt.penugasan.show', $kasus->id))
            ->assertOk()
            ->assertSee('Laporan akhir belum tersedia untuk kasus historis ini.')
            ->assertDontSee('Tambah Progress')
            ->assertDontSee('Selesaikan Penanganan');
    }

    public function test_historical_popt_can_read_but_cannot_mutate_after_reassignment(): void
    {
        $firstPopt = $this->user('popt');
        $secondPopt = $this->user('popt');
        [$kasus, $firstAssignment] = $this->assignment($firstPopt);
        $firstAssignment->update(['status' => PenugasanPopt::STATUS_DICABUT]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $kasus->id,
            'popt_id' => $secondPopt->id,
            'assigned_by' => $this->user('operator_uptd')->id,
            'deadline_at' => now()->addDays(4),
            'accepted_at' => null,
            'status' => PenugasanPopt::STATUS_AKTIF,
        ]);

        $this->actingAs($firstPopt)
            ->get(route('popt.penugasan.show', $kasus->id))
            ->assertOk()
            ->assertSee($kasus->kasus_code)
            ->assertDontSee('Terima Penugasan')
            ->assertDontSee('Tambah Progress')
            ->assertDontSee('Selesaikan Penanganan');
    }

    public function test_completion_failure_rolls_back_database_and_removes_new_files(): void
    {
        Storage::fake('public');
        $popt = $this->user('popt');
        [$kasus] = $this->assignment($popt, KasusPenanganan::STATUS_DALAM_PELAKSANAAN);
        $this->accept($popt, $kasus);

        $transition = Mockery::mock(StatusTransitionService::class);
        $transition->shouldReceive('pindahkan')->once()->andThrow(new \RuntimeException('simulated transition failure'));
        app()->instance(StatusTransitionService::class, $transition);

        $this->expectException(\RuntimeException::class);
        try {
            app(PoptHandlingService::class)->completeHandling(
                $kasus->id,
                $popt,
                'Ringkasan tindakan.',
                'Hasil penanganan.',
                'Rekomendasi tindak lanjut.',
                null,
                [UploadedFile::fake()->image('atomic.jpg')],
            );
        } finally {
            $this->assertDatabaseCount('laporan_akhir_penanganan', 0);
            $this->assertDatabaseCount('laporan_akhir_evidences', 0);
            $this->assertSame(KasusPenanganan::STATUS_DALAM_PELAKSANAAN, $kasus->fresh()->current_status);
            $this->assertSame([], Storage::disk('public')->allFiles('laporan-akhir'));
        }
    }

    public function test_web_detail_uses_action_based_popt_workflow(): void
    {
        $popt = $this->user('popt');
        [$kasus] = $this->assignment($popt);

        $this->actingAs($popt)
            ->get(route('popt.penugasan'))
            ->assertOk()
            ->assertSee('Target Penyelesaian')
            ->assertSee('Status Waktu');

        $this->actingAs($popt)
            ->get(route('popt.penugasan.show', $kasus->id))
            ->assertOk()
            ->assertSee('Terima Penugasan')
            ->assertDontSee('Status berikutnya');

        $this->accept($popt, $kasus);
        $this->actingAs($popt)
            ->get(route('popt.penugasan.show', $kasus->id))
            ->assertOk()
            ->assertSee('Tambah Progress')
            ->assertSee('Ajukan Perpanjangan')
            ->assertSee('Selesaikan Penanganan')
            ->assertSee('Dokumentasi Foto');
    }
}
