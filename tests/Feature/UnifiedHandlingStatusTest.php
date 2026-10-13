<?php

namespace Tests\Feature;

use App\Contracts\KomoditasReferensiClient;
use App\Models\DiagnosisResult;
use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\RefKomoditas;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use App\Services\MockKomoditasReferensiClient;
use App\Services\MonitoringCaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedHandlingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['poktan', 'popt', 'operator_uptd', 'admin', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);
        Storage::fake('public');
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function requestFor(User $owner, string $status = 'diajukan'): PermohonanPenanganan
    {
        $request = PermohonanPenanganan::factory()->create([
            'created_by' => $owner->id, 'status' => $status,
            'latitude_kasus' => -6.9, 'longitude_kasus' => 107.6,
            'kabupaten' => 'Kabupaten Bandung', 'kode_kabupaten' => '32.04',
        ]);
        DiagnosisResult::factory()->create([
            'diagnosis_id' => $request->diagnosis_id, 'ranking' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
        ]);

        return $request;
    }

    private function assignedCase(string $status, $deadline, ?User $owner = null): array
    {
        $owner ??= $this->user('poktan');
        $request = $this->requestFor($owner, 'diterima');
        $case = KasusPenanganan::factory()->withApproval()->create([
            'permohonan_id' => $request->id, 'current_status' => $status,
            'latitude_kasus' => -7.1, 'longitude_kasus' => 107.8,
        ]);
        $popt = $this->user('popt');
        $assignment = PenugasanPopt::factory()->create([
            'kasus_id' => $case->id, 'popt_id' => $popt->id,
            'assigned_by' => $this->user('operator_uptd')->id,
            'deadline_at' => $deadline, 'accepted_at' => $status === 'ditugaskan' ? null : now()->subDay(),
        ]);

        return [$request, $case, $popt, $assignment];
    }

    public function test_operator_decisions_branch_to_rejected_or_handling_and_keep_the_rejection_reason(): void
    {
        $owner = $this->user('poktan');
        $rejected = $this->requestFor($owner);
        $accepted = $this->requestFor($owner);
        Sanctum::actingAs($this->user('operator_uptd'));
        foreach ([$rejected, $accepted] as $request) {
            $this->postJson("/api/operator/permohonan/{$request->id}/review")->assertOk()
                ->assertJsonPath('data.monitoring_status', 'menunggu_review');
        }
        $this->postJson("/api/operator/permohonan/{$rejected->id}/reject", ['catatan' => 'Lokasi perlu diperbaiki.'])
            ->assertOk()->assertJsonPath('data.monitoring_status', 'ditolak')
            ->assertJsonPath('data.keputusan.catatan', 'Lokasi perlu diperbaiki.');
        $this->postJson("/api/operator/permohonan/{$accepted->id}/accept", ['popt_id' => $this->user('popt')->id, 'deadline_at' => now()->addDay()->toDateTimeString()])
            ->assertCreated()->assertJsonPath('data.monitoring_status', 'dalam_penanganan');

        $this->actingAs($owner)->get(route('permohonan.show', $rejected->id))
            ->assertOk()->assertViewHas('primaryStatus', fn ($status) => $status['key'] === 'ditolak' && ! $status['is_overdue'])
            ->assertSee('Alasan Penolakan')->assertSee('Lokasi perlu diperbaiki.');
        $this->get(route('permohonan.show', $accepted->id))->assertOk()
            ->assertViewHas('primaryStatus', fn ($status) => $status['key'] === 'dalam_penanganan');
        $this->get(route('permohonan.index', ['status' => 'ditolak']))->assertOk()
            ->assertSee($rejected->permohonan_code)->assertDontSee($accepted->permohonan_code);
        $this->assertDatabaseCount('kasus_penanganan', 1);
    }

    public function test_accept_start_and_append_progress_keep_one_display_state_and_ownership(): void
    {
        [$request, $case, $popt] = $this->assignedCase('ditugaskan', now()->addDay());
        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$case->id}/start")->assertUnprocessable()->assertJsonValidationErrors('accepted_at');
        $this->postJson("/api/popt/kasus/{$case->id}/accept")->assertOk()->assertJsonPath('data.monitoring_status', 'dalam_penanganan');
        $this->postJson("/api/popt/kasus/{$case->id}/start")->assertOk()
            ->assertJsonPath('data.monitoring_status', 'dalam_penanganan');
        $this->postJson("/api/popt/kasus/{$case->id}/progress", ['catatan' => 'Tindakan pertama.'])->assertCreated();
        $this->postJson("/api/popt/kasus/{$case->id}/progress", ['catatan' => 'Pengamatan lanjutan.'])->assertCreated();
        $this->getJson("/api/popt/kasus/{$case->id}")->assertOk()->assertJsonPath('data.monitoring_status', 'dalam_penanganan');
        $this->assertDatabaseCount('progres_penanganan', 2);
        $this->assertSame('dalam_pelaksanaan', $case->fresh()->current_status);
        $historyCount = $case->riwayatStatus()->count();
        $this->postJson("/api/popt/kasus/{$case->id}/start")->assertUnprocessable();
        $this->assertSame($historyCount, $case->riwayatStatus()->count());

        Sanctum::actingAs($this->user('popt'));
        $this->postJson("/api/popt/kasus/{$case->id}/start")->assertForbidden();
    }

    public function test_recording_actual_progress_also_starts_handling_for_existing_clients(): void
    {
        [, $case, $popt] = $this->assignedCase('sedang_direview', now()->addDay());
        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$case->id}/progress", ['catatan' => 'Pemeriksaan tanaman dilakukan.'])->assertCreated();
        $this->getJson("/api/popt/kasus/{$case->id}")->assertOk()->assertJsonPath('data.monitoring_status', 'dalam_penanganan');
        $this->assertDatabaseHas('riwayat_status_penanganan', ['kasus_id' => $case->id, 'status' => 'dalam_pelaksanaan']);
    }

    public function test_extension_pending_approved_and_rejected_use_only_the_effective_operator_deadline(): void
    {
        [$request, $case, $popt, $assignment] = $this->assignedCase('dalam_pelaksanaan', now()->subDay());
        $oldDeadline = $assignment->deadline_at;
        $newDeadline = now()->addDays(3)->startOfMinute();
        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$case->id}/perpanjangan", [
            'proposed_deadline_at' => $newDeadline->toDateTimeString(), 'reason' => 'Cuaca belum mendukung.',
        ])->assertCreated();
        $extension = $assignment->extensionRequests()->firstOrFail();
        $this->getJson("/api/popt/kasus/{$case->id}")->assertOk()->assertJsonPath('data.monitoring_status', 'dalam_penanganan')->assertJsonPath('data.is_overdue', true);
        $this->assertTrue($assignment->fresh()->deadline_at->equalTo($oldDeadline));

        $operator = $this->user('operator_uptd');
        Sanctum::actingAs($operator);
        $this->actingAs($operator)->post(route('operator.kasus.extension.approve', [$case->id, $extension->id]), ['review_note' => 'Tambahan waktu disetujui.'])->assertRedirect();
        $this->getJson("/api/kasus/{$case->id}")->assertOk()->assertJsonPath('data.monitoring_status', 'dalam_penanganan')->assertJsonPath('data.is_overdue', false);
        $this->assertTrue($assignment->fresh()->deadline_at->equalTo($newDeadline));

        $this->travelTo($newDeadline->copy()->addHour());
        Sanctum::actingAs($popt);
        $this->postJson("/api/popt/kasus/{$case->id}/perpanjangan", [
            'proposed_deadline_at' => now()->addDays(2)->toDateTimeString(), 'reason' => 'Pengamatan tambahan.',
        ])->assertCreated();
        $secondExtension = $assignment->extensionRequests()->latest('id')->firstOrFail();
        Sanctum::actingAs($operator);
        $this->actingAs($operator)->post(route('operator.kasus.extension.reject', [$case->id, $secondExtension->id]), ['review_note' => 'Gunakan target yang sudah disetujui.'])->assertRedirect();
        $this->getJson("/api/kasus/{$case->id}")->assertOk()->assertJsonPath('data.monitoring_status', 'dalam_penanganan')->assertJsonPath('data.is_overdue', true);
        $this->assertTrue($assignment->fresh()->deadline_at->equalTo($newDeadline));
        $this->actingAs(User::findOrFail($request->created_by))->get(route('permohonan.show', $request->id))
            ->assertOk()->assertSee('Disetujui')->assertSee('Usulan ini ditolak')
            ->assertDontSee('Tambahan waktu disetujui.')->assertDontSee('Gunakan target yang sudah disetujui.')
            ->assertSee('Anda tidak perlu mengajukan ulang');
    }

    public function test_completion_requires_report_and_photos_and_is_never_active_or_overdue(): void
    {
        [$request, $case, $popt] = $this->assignedCase('dalam_pelaksanaan', now()->subDay());
        Sanctum::actingAs($this->user('operator_uptd'));
        $this->postJson("/api/kasus/{$case->id}/status", ['status' => 'selesai'])->assertUnprocessable();
        Sanctum::actingAs($popt);
        $report = ['ringkasan_tindakan' => 'Pengendalian dilakukan.', 'hasil_penanganan' => 'Kondisi membaik.', 'rekomendasi' => 'Pantau tanaman.'];
        $this->postJson("/api/popt/kasus/{$case->id}/selesaikan", $report)->assertUnprocessable()->assertJsonValidationErrors('photos');
        $this->assertSame('dalam_pelaksanaan', $case->fresh()->current_status);
        $this->postJson("/api/popt/kasus/{$case->id}/selesaikan", $report + ['photos' => [UploadedFile::fake()->image('bukti.jpg')]])
            ->assertOk()->assertJsonPath('data.monitoring_status', 'selesai')->assertJsonPath('data.is_overdue', false)
            ->assertJsonPath('data.completion_evidence_missing', false)->assertJsonPath('data.final_report_exists', true);
        $this->assertDatabaseCount('laporan_akhir_evidences', 1);
        $this->assertSame(0, $case->penugasanPopt()->where('status', 'aktif')->count());
        $summary = app(MonitoringCaseService::class)->summary();
        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(0, $summary['active']);
        $this->assertSame(0, $summary['overdue']);
        $this->actingAs(User::findOrFail($request->created_by))->get(route('permohonan.show', $request->id))
            ->assertOk()->assertSee('Kondisi membaik.')->assertSee('bukti.jpg');
    }

    public function test_monitoring_map_report_filters_and_poktan_share_one_entry_one_status_and_actual_coordinates(): void
    {
        $owner = $this->user('poktan');
        $waiting = $this->requestFor($owner);
        $rejected = $this->requestFor($owner, 'ditolak');
        KeputusanPermohonan::create(['permohonan_id' => $rejected->id, 'keputusan' => 'ditolak', 'catatan' => 'Bukti belum sesuai.', 'operator_id' => $this->user('operator_uptd')->id, 'decided_at' => now()]);
        [$unstarted, $unstartedCase] = $this->assignedCase('ditugaskan', now()->subDay(), $owner);
        [$handling, $handlingCase] = $this->assignedCase('dalam_pelaksanaan', now()->subDay(), $owner);
        [$completed] = $this->assignedCase('selesai', now()->subDay(), $owner);
        [$legacy, $legacyCase] = $this->assignedCase('ditunda', now()->addDay(), $owner);
        RiwayatStatusPenanganan::create(['kasus_id' => $legacyCase->id, 'status' => 'ditunda', 'catatan' => 'Kendala akses lama.', 'created_at' => now()]);
        [, $archived] = $this->assignedCase('selesai', now()->subDay(), $owner);
        $archived->delete();

        $pimpinan = $this->user('pimpinan');
        Sanctum::actingAs($pimpinan);
        $records = collect($this->getJson('/api/monitoring/kasus')->assertOk()->assertJsonPath('meta.total', 6)->json('data'));
        $this->assertCount(6, $records->unique('permohonan_id'));
        $this->assertEquals(['menunggu_review' => 1, 'ditolak' => 1, 'dalam_penanganan' => 3, 'selesai' => 1], $records->countBy('monitoring_status')->all());
        $this->assertCount(2, $records->where('is_overdue', true));
        $this->assertSame(-6.9, $records->firstWhere('permohonan_id', $waiting->id)['latitude_kasus']);
        $this->assertSame(-7.1, $records->firstWhere('permohonan_id', $unstarted->id)['latitude_kasus']);
        $this->assertSame(107.8, $records->firstWhere('permohonan_id', $unstarted->id)['longitude_kasus']);
        $this->assertFalse($records->firstWhere('permohonan_id', $rejected->id)['is_overdue']);
        $this->assertSame('Bukti belum sesuai.', $records->firstWhere('permohonan_id', $rejected->id)['rejection_reason']);
        $this->getJson('/api/monitoring/kasus?per_page=2&page=3')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 6);

        $report = $this->actingAs($pimpinan)->get('/laporan-monitoring')->assertOk();
        $report->assertViewHas('summary', fn ($summary) => $summary['total'] === 6 && $summary['active'] === 3 && $summary['completed'] === 1 && $summary['overdue'] === 2 && array_sum($summary['by_status']) === 6);
        $reportRows = collect($report->viewData('kasus')->items());
        foreach ($records as $record) {
            $row = $reportRows->firstWhere('monitoring_id', $record['monitoring_id']);
            $this->assertSame($record['monitoring_status'], $row['monitoring_status']);
            $this->assertSame($record['is_overdue'], $row['is_overdue']);
        }
        $this->get('/laporan-monitoring?status=dalam_penanganan&overdue=1')->assertOk()
            ->assertSee($handlingCase->kasus_code)->assertSee($unstartedCase->kasus_code)->assertDontSee($legacyCase->kasus_code)
            ->assertViewHas('summary', fn ($summary) => $summary['total'] === 2 && $summary['active'] === 2 && $summary['overdue'] === 2);
        $this->getJson('/api/monitoring/kasus?status=melewati_batas_waktu')->assertUnprocessable();

        $this->actingAs($owner);
        foreach ($records as $record) {
            $this->get(route('permohonan.show', $record['permohonan_id']))->assertOk()
                ->assertViewHas('primaryStatus', fn ($status) => $status['key'] === $record['monitoring_status'] && $status['is_overdue'] === $record['is_overdue']);
        }
        $this->assertSame('ditunda', $legacyCase->fresh()->current_status);
        $this->assertSame('Kendala akses lama.', $legacyCase->riwayatStatus()->first()->catatan);
        $this->assertSame(1, $legacyCase->riwayatStatus()->count());
    }

    public function test_poktan_scope_and_pimpinan_read_only_apply_to_new_and_existing_routes(): void
    {
        [$request, $case] = $this->assignedCase('ditugaskan', now()->addDay());
        $other = $this->user('poktan');
        $this->actingAs($other)->get(route('permohonan.show', $request->id))->assertNotFound();
        $this->get(route('permohonan.index'))->assertOk()->assertDontSee($request->permohonan_code);
        Sanctum::actingAs($other);
        $this->getJson("/api/permohonan/{$request->id}")->assertNotFound();
        $this->getJson('/api/monitoring/kasus')->assertForbidden();
        $this->getJson("/api/kasus/{$case->id}")->assertForbidden();

        Sanctum::actingAs($this->user('pimpinan'));
        $this->getJson('/api/monitoring/kasus')->assertOk()->assertJsonPath('meta.total', 1);
        $this->postJson("/api/operator/permohonan/{$request->id}/reject", ['catatan' => 'Tidak berwenang.'])->assertForbidden();
        $this->postJson("/api/kasus/{$case->id}/assign-popt")->assertForbidden();
        $this->postJson("/api/popt/kasus/{$case->id}/start")->assertForbidden();
        $this->postJson("/api/popt/kasus/{$case->id}/selesaikan")->assertForbidden();
        $this->deleteJson("/api/kasus/{$case->id}")->assertForbidden();
        $this->postJson('/api/monitoring/kasus')->assertMethodNotAllowed();
    }

    public function test_pre_case_reference_filters_and_cancelled_request_scope_are_preserved(): void
    {
        $commodity = RefKomoditas::create(['kode' => 'KP-MON', 'nama' => 'Kopi Monitoring', 'is_verified' => true, 'sync_status' => 'synced']);
        $request = $this->requestFor($this->user('poktan'));
        $request->diagnosis->update(['commodity_id' => $commodity->id]);
        Sanctum::actingAs($this->user('pimpinan'));
        $this->getJson('/api/monitoring/kasus?status=menunggu_review&commodity=Kopi+Monitoring&disease=Karat+Daun+Kopi&regency=Kabupaten+Bandung')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.komoditas.nama', 'Kopi Monitoring');
        $this->get('/laporan-monitoring?commodity=Kopi+Monitoring')->assertOk()->assertSee($request->permohonan_code);
        [, $case] = $this->assignedCase('ditugaskan', now()->addDay());
        Sanctum::actingAs($this->user('operator_uptd'));
        $this->postJson("/api/kasus/{$case->id}/batal", ['alasan' => 'Periksa kembali lokasi.'])->assertOk();
        Sanctum::actingAs($this->user('pimpinan'));
        $cancelled = collect($this->getJson('/api/monitoring/kasus')->assertOk()->json('data'))->firstWhere('permohonan_id', $case->permohonan_id);
        $this->assertNull($cancelled['kasus_id']);
        $this->assertSame('menunggu_review', $cancelled['monitoring_status']);
        $this->assertTrue($case->fresh()->trashed());
    }
}
