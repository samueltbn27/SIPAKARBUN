<?php

namespace Tests\Unit\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\ProgresPenanganan;
use App\Models\RiwayatStatusPenanganan;
use App\Services\MonitoringStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private MonitoringStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MonitoringStatusService;
    }

    public function test_approval_assignment_and_target_mean_handling_before_field_work_starts(): void
    {
        foreach ([
            KasusPenanganan::STATUS_DITERIMA,
            KasusPenanganan::STATUS_DITUGASKAN,
            KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
        ] as $status) {
            $case = KasusPenanganan::factory()->withApproval()->create(['current_status' => $status]);

            PenugasanPopt::factory()->create(['kasus_id' => $case->id, 'deadline_at' => '2026-09-20 17:00:00']);
            $result = $this->service->resolve($case, Carbon::parse('2026-09-17 10:00:00'));

            $this->assertSame(MonitoringStatusService::STATUS_DALAM_PENANGANAN, $result['key']);
            $this->assertSame('Dalam Penanganan', $result['label']);
            $this->assertFalse($result['is_overdue']);
        }
    }

    public function test_active_technical_statuses_map_to_in_progress_monitoring(): void
    {
        foreach ([
            KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
            KasusPenanganan::STATUS_DITUNDA,
        ] as $status) {
            $case = KasusPenanganan::factory()->withApproval()->create(['current_status' => $status]);
            PenugasanPopt::factory()->create(['kasus_id' => $case->id, 'deadline_at' => now()->addDay()]);

            $this->assertSame(
                MonitoringStatusService::STATUS_DALAM_PENANGANAN,
                $this->service->resolve($case)['key'],
            );
        }
    }

    public function test_legacy_postponed_case_maps_to_handling_and_preserves_its_state_and_reason(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create([
            'current_status' => KasusPenanganan::STATUS_DITUNDA,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => Carbon::parse('2026-09-20 17:00:00'),
        ]);
        RiwayatStatusPenanganan::create([
            'kasus_id' => $case->id, 'previous_status' => 'sedang_direview',
            'status' => 'ditunda', 'catatan' => 'Akses kebun terputus.', 'created_at' => now(),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_DALAM_PENANGANAN, $result['key']);
        $this->assertSame('ditunda', $result['legacy_status']);
        $this->assertSame('Akses kebun terputus.', $result['legacy_reason']);
        $this->assertSame('ditunda', $case->fresh()->current_status);
        $this->assertDatabaseCount('riwayat_status_penanganan', 1);
        $this->assertFalse($result['is_overdue']);
    }

    public function test_overdue_assignment_is_computed_even_when_popt_has_not_accepted(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create([
            'current_status' => KasusPenanganan::STATUS_DITUGASKAN,
        ]);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'accepted_at' => null,
            'deadline_at' => Carbon::parse('2026-09-16 17:00:00'),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_DALAM_PENANGANAN, $result['key']);
        $this->assertSame('Dalam Penanganan', $result['label']);
        $this->assertTrue($result['is_overdue']);
        $this->assertTrue($result['effective_deadline_at']->equalTo($assignment->deadline_at));
        $this->assertTrue($result['overdue_since']->equalTo($assignment->deadline_at));
    }

    public function test_legacy_postponed_overdue_case_keeps_handling_status_and_separate_lateness(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create([
            'current_status' => KasusPenanganan::STATUS_DITUNDA,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => Carbon::parse('2026-09-16 17:00:00'),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_DALAM_PENANGANAN, $result['key']);
        $this->assertTrue($result['is_overdue']);
        $this->assertSame(KasusPenanganan::STATUS_DITUNDA, $case->current_status);
    }

    public function test_completed_case_overrides_an_expired_deadline(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create([
            'current_status' => KasusPenanganan::STATUS_SELESAI,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => Carbon::parse('2026-09-16 17:00:00'),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_SELESAI, $result['key']);
        $this->assertFalse($result['is_overdue']);
    }

    public function test_active_assignment_without_deadline_is_never_overdue(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create([
            'current_status' => KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => null,
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2030-01-01 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_MENUNGGU_REVIEW, $result['key']);
        $this->assertFalse($result['is_overdue']);
        $this->assertNull($result['effective_deadline_at']);
        $this->assertTrue($result['history_incomplete']);
    }

    public function test_only_four_primary_labels_and_request_branching_are_exposed(): void
    {
        $this->assertSame(['Menunggu Review', 'Ditolak', 'Dalam Penanganan', 'Selesai'], array_values($this->service->labels()));
        foreach (['diajukan' => 'menunggu_review', 'sedang_direview' => 'menunggu_review', 'ditolak' => 'ditolak', 'diterima' => 'menunggu_review'] as $technical => $key) {
            $request = PermohonanPenanganan::factory()->create(['status' => $technical]);
            $result = $this->service->resolveRequest($request);
            $this->assertSame($key, $result['key']);
            $this->assertFalse($result['is_overdue']);
        }
    }

    public function test_display_and_query_filters_agree_for_every_technical_state_and_overdue_flag(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 12:00:00'));
        foreach (config('kasus.statuses') as $technical) {
            $case = KasusPenanganan::factory()->withApproval()->create(['current_status' => $technical]);
            PenugasanPopt::factory()->create(['kasus_id' => $case->id, 'deadline_at' => now()->subDay()]);
        }
        $cases = KasusPenanganan::all();
        foreach (array_keys($this->service->labels()) as $key) {
            $resolvedIds = $cases->filter(fn ($case) => $this->service->resolve($case)['key'] === $key)->values()->modelKeys();
            $this->assertSame($resolvedIds, $this->service->applyFilter(KasusPenanganan::query(), $key)->orderBy('id')->pluck('id')->all());
        }
        $overdueIds = $cases->filter(fn ($case) => $this->service->resolve($case)['is_overdue'])->values()->modelKeys();
        $this->assertSame($overdueIds, $this->service->applyOverdueFilter(KasusPenanganan::query())->orderBy('id')->pluck('id')->all());
        $this->assertNotContains('selesai', $this->service->applyOverdueFilter(KasusPenanganan::query())->pluck('current_status')->all());
    }

    public function test_old_review_case_with_actual_progress_is_still_in_handling_without_rewriting_history(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create(['current_status' => 'sedang_direview']);
        $assignment = PenugasanPopt::factory()->create(['kasus_id' => $case->id, 'deadline_at' => now()->addDay()]);
        ProgresPenanganan::create(['kasus_id' => $case->id, 'penugasan_popt_id' => $assignment->id, 'catatan' => 'Pengendalian telah dimulai.']);
        $this->assertSame('dalam_penanganan', $this->service->resolve($case)['key']);
        $this->assertTrue($this->service->applyFilter(KasusPenanganan::query(), 'dalam_penanganan')->whereKey($case->id)->exists());
        $this->assertFalse($this->service->applyFilter(KasusPenanganan::query(), 'menunggu_review')->whereKey($case->id)->exists());
        $this->assertSame('sedang_direview', $case->fresh()->current_status);
    }

    public function test_deadline_is_overdue_only_after_the_boundary_and_revoked_assignments_are_ignored(): void
    {
        $case = KasusPenanganan::factory()->withApproval()->create();
        $deadline = Carbon::parse('2026-10-13 12:00:00');
        $assignment = PenugasanPopt::factory()->create(['kasus_id' => $case->id, 'deadline_at' => $deadline]);
        $this->assertFalse($this->service->resolve($case->fresh(), $deadline)['is_overdue']);
        $this->assertTrue($this->service->resolve($case->fresh(), $deadline->copy()->addSecond())['is_overdue']);
        $assignment->update(['status' => 'dicabut']);
        $this->assertFalse($this->service->resolve($case->fresh(), $deadline->copy()->addDay())['is_overdue']);
    }

    public function test_incomplete_historical_records_are_disclosed_and_filters_agree_without_backfill(): void
    {
        foreach (['missing_decision', 'request_mismatch', 'missing_assignment', 'missing_deadline', 'invalid_deadline', 'revoked_assignment'] as $problem) {
            $case = KasusPenanganan::factory()->withApproval()->create(['current_status' => 'sedang_direview']);
            $assignment = PenugasanPopt::factory()->create(['kasus_id' => $case->id, 'deadline_at' => now()->subDay()]);
            match ($problem) {
                'missing_decision' => $case->permohonan->keputusan()->delete(),
                'request_mismatch' => $case->permohonan->update(['status' => 'diajukan']),
                'missing_assignment' => $assignment->delete(),
                'missing_deadline' => $assignment->update(['deadline_at' => null]),
                'invalid_deadline' => DB::table('penugasan_popt')->where('id', $assignment->id)->update(['deadline_at' => 'invalid-date']),
                'revoked_assignment' => $assignment->update(['status' => 'dicabut']),
            };
            $case->refresh();
            $before = $case->getRawOriginal();
            $result = $this->service->resolve($case);
            $this->assertSame('menunggu_review', $result['key'], $problem);
            $this->assertTrue($result['history_incomplete'], $problem);
            $this->assertNotEmpty($result['history_issues']);
            $this->assertStringContainsString('Data historis perlu ditinjau Operator.', $result['history_note']);
            $this->assertFalse($result['is_overdue']);
            $this->assertTrue($this->service->applyFilter(KasusPenanganan::query(), 'menunggu_review')->whereKey($case->id)->exists(), $problem);
            $this->assertFalse($this->service->applyFilter(KasusPenanganan::query(), 'dalam_penanganan')->whereKey($case->id)->exists(), $problem);
            $this->assertFalse($this->service->applyOverdueFilter(KasusPenanganan::query())->whereKey($case->id)->exists(), $problem);
            $this->assertSame($before, $case->fresh()->getRawOriginal());
        }
    }
}
