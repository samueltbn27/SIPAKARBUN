<?php

namespace Tests\Unit\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Services\MonitoringStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_waiting_mapping_for_received_and_assigned_cases(): void
    {
        foreach ([
            KasusPenanganan::STATUS_DITERIMA,
            KasusPenanganan::STATUS_DITUGASKAN,
        ] as $status) {
            $case = KasusPenanganan::factory()->create(['current_status' => $status]);

            $result = $this->service->resolve($case, Carbon::parse('2026-09-17 10:00:00'));

            $this->assertSame(MonitoringStatusService::STATUS_MENUNGGU_PENANGANAN, $result['key']);
            $this->assertSame('Menunggu Penanganan', $result['label']);
            $this->assertFalse($result['is_overdue']);
        }
    }

    public function test_active_technical_statuses_map_to_in_progress_monitoring(): void
    {
        foreach ([
            KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            KasusPenanganan::STATUS_SIAP_DIEKSEKUSI,
            KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
        ] as $status) {
            $case = KasusPenanganan::factory()->create(['current_status' => $status]);

            $this->assertSame(
                MonitoringStatusService::STATUS_DALAM_PENANGANAN,
                $this->service->resolve($case)['key'],
            );
        }
    }

    public function test_postponed_case_maps_to_postponed_when_deadline_is_future(): void
    {
        $case = KasusPenanganan::factory()->create([
            'current_status' => KasusPenanganan::STATUS_DITUNDA,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => Carbon::parse('2026-09-20 17:00:00'),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_DITUNDA, $result['key']);
        $this->assertFalse($result['is_overdue']);
    }

    public function test_overdue_assignment_is_computed_even_when_popt_has_not_accepted(): void
    {
        $case = KasusPenanganan::factory()->create([
            'current_status' => KasusPenanganan::STATUS_DITUGASKAN,
        ]);
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'accepted_at' => null,
            'deadline_at' => Carbon::parse('2026-09-16 17:00:00'),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_MELEWATI_BATAS_WAKTU, $result['key']);
        $this->assertSame('Melewati Batas Waktu', $result['label']);
        $this->assertTrue($result['is_overdue']);
        $this->assertTrue($result['effective_deadline_at']->equalTo($assignment->deadline_at));
        $this->assertTrue($result['overdue_since']->equalTo($assignment->deadline_at));
    }

    public function test_postponed_overdue_case_keeps_technical_status_but_uses_overdue_monitoring(): void
    {
        $case = KasusPenanganan::factory()->create([
            'current_status' => KasusPenanganan::STATUS_DITUNDA,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => Carbon::parse('2026-09-16 17:00:00'),
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2026-09-17 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_MELEWATI_BATAS_WAKTU, $result['key']);
        $this->assertTrue($result['is_overdue']);
        $this->assertSame(KasusPenanganan::STATUS_DITUNDA, $case->current_status);
    }

    public function test_completed_case_overrides_an_expired_deadline(): void
    {
        $case = KasusPenanganan::factory()->create([
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
        $case = KasusPenanganan::factory()->create([
            'current_status' => KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
        ]);
        PenugasanPopt::factory()->create([
            'kasus_id' => $case->id,
            'deadline_at' => null,
        ]);

        $result = $this->service->resolve($case->fresh(), Carbon::parse('2030-01-01 10:00:00'));

        $this->assertSame(MonitoringStatusService::STATUS_DALAM_PENANGANAN, $result['key']);
        $this->assertFalse($result['is_overdue']);
        $this->assertNull($result['effective_deadline_at']);
    }
}
