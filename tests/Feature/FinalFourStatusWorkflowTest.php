<?php

namespace Tests\Feature;

use App\Contracts\KomoditasReferensiClient;
use App\Models\DiagnosisResult;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\KasusService;
use App\Services\MockKomoditasReferensiClient;
use App\Services\MonitoringCaseService;
use App\Services\MonitoringStatusService;
use App\Services\PermohonanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinalFourStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['poktan', 'popt', 'operator_uptd', 'admin', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);
    }

    private function user(string $role, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->assignRole($role);

        return $user;
    }

    private function reviewedRequest(User $owner, User $operator): PermohonanPenanganan
    {
        $request = PermohonanPenanganan::factory()->sedangDireview()->create([
            'created_by' => $owner->id, 'reviewed_by' => $operator->id, 'reviewed_at' => now(),
        ]);
        DiagnosisResult::factory()->create(['diagnosis_id' => $request->diagnosis_id, 'ranking' => 1]);

        return $request;
    }

    public static function invalidApprovalInput(): array
    {
        return [
            'missing POPT' => ['missing_popt', 'popt_id'],
            'missing target' => ['missing_target', 'deadline_at'],
            'invalid date' => ['invalid_date', 'deadline_at'],
            'expired target' => ['past', 'deadline_at'],
            'nonexistent POPT' => ['absent', 'popt_id'],
            'inactive POPT' => ['inactive', 'popt_id'],
            'wrong role' => ['wrong_role', 'popt_id'],
        ];
    }

    #[DataProvider('invalidApprovalInput')]
    public function test_invalid_approval_never_creates_a_partial_decision_case_or_assignment(string $scenario, string $field): void
    {
        $owner = $this->user('poktan');
        $operator = $this->user('operator_uptd');
        $request = $this->reviewedRequest($owner, $operator);
        $popt = $this->user($scenario === 'wrong_role' ? 'poktan' : 'popt', $scenario !== 'inactive');
        $data = ['popt_id' => $popt->id, 'deadline_at' => now()->addDay()->toDateTimeString()];
        match ($scenario) {
            'missing_popt' => $data = array_diff_key($data, ['popt_id' => true]),
            'missing_target' => $data = array_diff_key($data, ['deadline_at' => true]),
            'invalid_date' => $data['deadline_at'] = 'bukan-tanggal',
            'past' => $data['deadline_at'] = now()->subMinute()->toDateTimeString(),
            'absent' => $data['popt_id'] = 999999,
            default => null,
        };
        Sanctum::actingAs($operator);
        $this->postJson("/api/operator/permohonan/{$request->id}/accept", $data)
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame('sedang_direview', $request->fresh()->status);
        foreach (['keputusan_permohonan', 'kasus_penanganan', 'penugasan_popt', 'riwayat_status_penanganan'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame('menunggu_review', app(MonitoringStatusService::class)->resolveRequest($request->fresh())['key']);
    }

    public function test_web_approval_form_preserves_required_inputs_and_approves_with_assignment_immediately(): void
    {
        $operator = $this->user('operator_uptd');
        $owner = $this->user('poktan');
        $popt = $this->user('popt');
        $inactive = $this->user('popt', false);
        $request = $this->reviewedRequest($owner, $operator);
        $this->actingAs($operator)->get(route('operator.permohonan.show', $request->id))->assertOk()
            ->assertSee('name="popt_id"', false)->assertSee('name="deadline_at"', false)
            ->assertSee($popt->name)->assertDontSee($inactive->name)->assertSee('Terima dan Tugaskan POPT');
        $this->from(route('operator.permohonan.show', $request->id))->post(route('operator.permohonan.accept', $request->id), [
            'popt_id' => $popt->id, 'deadline_at' => 'bukan-tanggal', 'catatan' => 'Catatan penerimaan tetap ada.',
        ])->assertSessionHasErrors('deadline_at')->assertSessionHasInput('popt_id', $popt->id)
            ->assertSessionHasInput('catatan', 'Catatan penerimaan tetap ada.');
        $target = now()->addDays(3)->startOfMinute();
        $this->post(route('operator.permohonan.accept', $request->id), [
            'popt_id' => $popt->id, 'deadline_at' => $target->toDateTimeString(),
        ])->assertRedirect(route('operator.permohonan.show', $request->id))->assertSessionHasNoErrors();
        $case = $request->fresh()->kasus;
        $this->assertSame('ditugaskan', $case->current_status);
        $this->assertSame($popt->id, $case->penugasanAktif->popt_id);
        $this->assertTrue($case->penugasanAktif->deadline_at->equalTo($target));
        $this->assertNull($case->penugasanAktif->accepted_at);
        $this->assertSame(0, $case->progress()->count());
        $this->assertFalse($case->riwayatStatus()->where('status', 'dalam_pelaksanaan')->exists());
        $status = app(MonitoringStatusService::class)->resolve($case);
        $this->assertSame('dalam_penanganan', $status['key']);
        $this->assertFalse($status['history_incomplete']);
        $this->actingAs($owner)->get(route('permohonan.show', $request->id))->assertOk()
            ->assertSee('Dalam Penanganan')->assertSee($popt->name)->assertSee('Kegiatan lapangan ditampilkan sesuai catatan aktual')
            ->assertDontSee('Penanganan dimulai');
    }

    public function test_failure_during_assignment_rolls_back_the_entire_approval(): void
    {
        $operator = $this->user('operator_uptd');
        $request = $this->reviewedRequest($this->user('poktan'), $operator);
        $popt = $this->user('popt');
        $this->mock(KasusService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('assignPopt')->once()->andThrow(new RuntimeException('Simulated assignment failure.'));
        });
        try {
            app(PermohonanService::class)->terima($request, $operator, null, $popt->id, now()->addDay()->toDateTimeString());
            $this->fail('Expected assignment failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated assignment failure.', $exception->getMessage());
        }
        $this->assertSame('sedang_direview', $request->fresh()->status);
        foreach (['keputusan_permohonan', 'kasus_penanganan', 'penugasan_popt', 'riwayat_status_penanganan'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_stale_approval_review_and_rejection_cannot_overwrite_the_winning_decision(): void
    {
        $operator = $this->user('operator_uptd');
        $other = $this->user('operator_uptd');
        $request = $this->reviewedRequest($this->user('poktan'), $operator);
        $stale = PermohonanPenanganan::findOrFail($request->id);
        $popt = $this->user('popt');
        $service = app(PermohonanService::class);
        $case = $service->terima($request, $operator, null, $popt->id, now()->addDay()->toDateTimeString());
        $history = $case->riwayatStatus()->get()->toArray();
        foreach (['accept', 'review', 'reject'] as $action) {
            try {
                match ($action) {
                    'accept' => $service->terima($stale, $other, null, $popt->id, now()->addDays(2)->toDateTimeString()),
                    'review' => $service->review($stale, $other->id),
                    'reject' => $service->tolak($stale, $other, 'Stale decision must fail.'),
                };
                $this->fail('Stale action must fail: '.$action);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('permohonan_id', $exception->errors());
            }
        }
        Sanctum::actingAs($other);
        $this->postJson("/api/operator/permohonan/{$request->id}/accept", [
            'popt_id' => $popt->id, 'deadline_at' => now()->addDays(2)->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('permohonan_id');
        $this->assertSame('diterima', $request->fresh()->status);
        $this->assertDatabaseCount('keputusan_permohonan', 1);
        $this->assertDatabaseCount('kasus_penanganan', 1);
        $this->assertDatabaseCount('penugasan_popt', 1);
        $this->assertSame($history, $case->riwayatStatus()->get()->toArray());
    }

    public function test_rejection_reason_is_required_and_no_handling_assignment_is_created(): void
    {
        $operator = $this->user('operator_uptd');
        $request = $this->reviewedRequest($this->user('poktan'), $operator);
        Sanctum::actingAs($operator);
        $this->postJson("/api/operator/permohonan/{$request->id}/reject", ['catatan' => '   '])
            ->assertUnprocessable()->assertJsonValidationErrors('catatan');
        $this->assertSame('sedang_direview', $request->fresh()->status);
        $this->assertDatabaseCount('penugasan_popt', 0);
        $this->postJson("/api/operator/permohonan/{$request->id}/reject", ['catatan' => 'Bukti belum memadai.'])
            ->assertOk()->assertJsonPath('data.monitoring_status', 'ditolak')
            ->assertJsonPath('data.keputusan.catatan', 'Bukti belum memadai.');
        $this->assertDatabaseCount('kasus_penanganan', 0);
    }

    public function test_every_role_page_and_monitoring_filter_exposes_only_the_four_primary_options(): void
    {
        $operator = $this->user('operator_uptd');
        $owner = $this->user('poktan');
        $request = $this->reviewedRequest($owner, $operator);
        $popt = $this->user('popt');
        $case = app(PermohonanService::class)->terima($request, $operator, null, $popt->id, now()->addDay()->toDateTimeString());
        foreach ([[$operator, '/operator/permohonan'], [$operator, '/operator/kasus'], [$popt, '/popt/penugasan'], [$owner, '/permohonan'], [$this->user('pimpinan'), '/laporan-monitoring']] as [$actor, $url]) {
            $response = $this->actingAs($actor)->get($url)->assertOk();
            foreach (['menunggu_review', 'ditolak', 'dalam_penanganan', 'selesai'] as $key) {
                $response->assertSee('value="'.$key.'"', false);
            }
            $response->assertDontSee('Menunggu Penanganan')->assertDontSee('value="ditunda"', false);
            $this->get($url.'?status=dalam_penanganan')->assertOk()->assertSee($url === '/operator/permohonan' ? $request->permohonan_code : $case->kasus_code);
        }
        Sanctum::actingAs($this->user('pimpinan'));
        $this->getJson('/api/monitoring/kasus?status=menunggu_penanganan')->assertUnprocessable();
        $this->getJson('/api/monitoring/kasus?status=dalam_penanganan')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.monitoring_status', 'dalam_penanganan');
    }

    public function test_invalid_historical_target_remains_readable_and_flagged_without_rewriting_history(): void
    {
        $operator = $this->user('operator_uptd');
        $owner = $this->user('poktan');
        $popt = $this->user('popt');
        $request = $this->reviewedRequest($owner, $operator);
        $case = app(PermohonanService::class)->terima($request, $operator, null, $popt->id, now()->addDay()->toDateTimeString());
        $assignment = $case->penugasanAktif;
        DB::table('penugasan_popt')->where('id', $assignment->id)
            ->update(['deadline_at' => 'invalid-legacy-target', 'accepted_at' => now()]);
        $history = $case->riwayatStatus()->get()->toArray();

        foreach ([[$owner, '/permohonan/'.$request->id], [$operator, '/operator/kasus/'.$case->id], [$popt, '/popt/penugasan/'.$case->id], [$popt, '/popt/penugasan'], [$this->user('pimpinan'), '/laporan-monitoring']] as [$actor, $url]) {
            $this->actingAs($actor)->get($url)->assertOk()
                ->assertSee('Menunggu Review')->assertSee('Data historis perlu ditinjau Operator.');
        }

        Sanctum::actingAs($operator);
        $this->getJson('/api/kasus/'.$case->id)->assertOk()
            ->assertJsonPath('data.monitoring_status', 'menunggu_review')
            ->assertJsonPath('data.history_incomplete', true)
            ->assertJsonPath('data.penugasan_popt.deadline_at', null);
        $this->getJson('/api/monitoring/kasus?status=menunggu_review')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/monitoring/kasus?status=dalam_penanganan')->assertOk()->assertJsonPath('meta.total', 0);
        $summary = app(MonitoringCaseService::class)->summary();
        $this->assertSame(1, $summary['review']);
        $this->assertSame(0, $summary['active']);
        $this->assertSame(0, $summary['overdue']);
        $this->assertSame('invalid-legacy-target', $assignment->fresh()->getRawOriginal('deadline_at'));
        $this->assertSame($history, $case->riwayatStatus()->get()->toArray());
    }
}
