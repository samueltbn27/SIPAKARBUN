<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Models\AturanCf;
use App\Models\Diagnosis;
use App\Models\Gejala;
use App\Models\KasusPenanganan;
use App\Models\LaporanAkhirEvidence;
use App\Models\LaporanAkhirPenanganan;
use App\Models\PenugasanPopt;
use App\Models\Penyakit;
use App\Models\PermohonanPenanganan;
use App\Models\PerpanjanganPenugasan;
use App\Models\ProgresPenanganan;
use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Models\User;
use App\Services\CertaintyFactorService;
use App\Services\LaporanAkhirEvidenceService;
use App\Services\LocalKelompokTaniReferensiClient;
use App\Services\MonitoringCaseService;
use App\Services\MonitoringStatusService;
use App\Support\PublicStorageUrl;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SipakarbunDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SipakarbunDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'SIPAKARBUN-Tester-2026!';

    private const DISTRIBUTION = ['menunggu_review' => 3, 'ditolak' => 3, 'dalam_penanganan' => 10, 'selesai' => 4];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.uat.demo_password' => self::PASSWORD]);
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->app->bind(KelompokTaniReferensiClient::class, LocalKelompokTaniReferensiClient::class);
        $this->travelTo(now()->setDate(2026, 10, 13)->setTime(12, 0));
    }

    public function test_fresh_demo_has_four_statuses_complete_relations_and_offline_references(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        $this->assertSame(10, User::count());
        $this->assertSame(5, RefKelompokTani::count());
        $this->assertSame(41, RefKomoditas::count());
        $this->assertSame(0, RefKomoditas::whereNotNull('disbun_record_id')->count());
        $this->assertSame(4, Penyakit::count());
        $this->assertSame(24, Gejala::count());
        $this->assertSame(24, AturanCf::count());
        $this->assertSame(20, Diagnosis::count());
        $this->assertSame(20, PermohonanPenanganan::count());
        $this->assertSame(14, KasusPenanganan::count());
        $this->assertSame(14, PenugasanPopt::count());
        $this->assertSame(24, ProgresPenanganan::count());
        $this->assertSame(4, LaporanAkhirPenanganan::count());
        $this->assertSame(8, LaporanAkhirEvidence::count());
        $this->assertSame(3, PerpanjanganPenugasan::count());
        $summary = app(MonitoringCaseService::class)->summary();
        $this->assertSame(self::DISTRIBUTION, $summary['by_status']);
        $this->assertSame(20, $summary['total']);
        $this->assertSame(10, $summary['active']);
        $this->assertSame(4, $summary['completed']);
        $this->assertSame(5, $summary['overdue']);
        $this->assertSame($summary['total'], array_sum($summary['by_status']));
        $this->assertSame(0, KasusPenanganan::where('current_status', 'ditunda')->count());
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));

        foreach (PermohonanPenanganan::with(['diagnosis.symptoms', 'diagnosis.results', 'creator.kelompokTani', 'keputusan', 'kasus.penugasanTerakhir.popt'])->get() as $request) {
            $this->assertSame($request->created_by, $request->diagnosis->user_id);
            $this->assertSame($request->kelompok_tani_id, (int) $request->creator->kelompok_tani_id);
            $this->assertSame($request->diagnosis->commodity_id, $request->creator->kelompokTani->commodity_ref_id);
            $this->assertTrue($request->creator->hasRole('poktan'));
            $this->assertTrue($request->creator->hasAvailablePoktan());
            $this->assertStringStartsWith('DEMO FIKTIF ', $request->kelompok_tani_name_snapshot);
            $this->assertStringStartsWith('DEMO-SIPAKARBUN-V2-', $request->creator->kelompokTani->disbun_record_id);
            $this->assertSame(2, $request->diagnosis->symptoms->count());
            $trace = $request->diagnosis->results->first()->trace_snapshot;
            $this->assertSame(app(CertaintyFactorService::class)->combine(array_column($trace, 'cf_gejala')), (float) $request->diagnosis->results->first()->cf_value);
            if (in_array($request->status, ['diajukan', 'sedang_direview'], true)) {
                $this->assertNull($request->keputusan);
                $this->assertNull($request->kasus);
            } else {
                $this->assertSame($request->status, $request->keputusan->keputusan);
                $this->assertTrue($request->keputusan->operator->hasRole('operator_uptd'));
                if ($request->status === 'ditolak') {
                    $this->assertNotEmpty($request->keputusan->catatan);
                    $this->assertNull($request->kasus);
                } else {
                    $this->assertTrue($request->kasus->penugasanTerakhir->popt->hasRole('popt'));
                    $this->assertSame($request->latitude_kasus, $request->kasus->latitude_kasus);
                    $this->assertSame($request->longitude_kasus, $request->kasus->longitude_kasus);
                }
            }
            $this->assertGreaterThanOrEqual(-90, $request->latitude_kasus);
            $this->assertLessThanOrEqual(90, $request->latitude_kasus);
            $this->assertGreaterThanOrEqual(-180, $request->longitude_kasus);
            $this->assertLessThanOrEqual(180, $request->longitude_kasus);
            $this->assertNotSame((float) $request->creator->kelompokTani->latitude, $request->latitude_kasus);
            $this->assertNotSame((float) $request->creator->kelompokTani->longitude, $request->longitude_kasus);
        }
        $this->assertCount(2, PenugasanPopt::select('popt_id')->distinct()->get());
        $this->assertCount(5, app(KelompokTaniReferensiClient::class)->all());
        Http::assertNothingSent();
    }

    public function test_historical_timeline_obeys_workflow_and_no_future_completion_is_created(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        $this->assertTrue(PermohonanPenanganan::oldest()->first()->created_at->isBefore(now()->subMonths(6)));
        foreach (PermohonanPenanganan::with(['diagnosis', 'keputusan', 'kasus.penugasanTerakhir', 'kasus.progress', 'kasus.riwayatStatus', 'kasus.finalReport.evidences'])->get() as $request) {
            $this->assertTrue($request->diagnosis->created_at->lte($request->created_at));
            $this->assertTrue($request->created_at->lte(now()));
            if ($request->reviewed_at) {
                $this->assertTrue($request->created_at->lte($request->reviewed_at));
            }
            if ($request->keputusan) {
                $this->assertTrue($request->reviewed_at->lte($request->keputusan->decided_at));
            }
            if (! $request->kasus) {
                continue;
            }
            $case = $request->kasus;
            $assignment = $case->penugasanTerakhir;
            $this->assertTrue($request->keputusan->decided_at->lte($assignment->assigned_at));
            $this->assertTrue($assignment->assigned_at->lt($assignment->deadline_at));
            $history = $case->riwayatStatus->sortBy(fn ($entry) => [$entry->created_at->getTimestamp(), $entry->id])->values();
            foreach ($history as $index => $entry) {
                $this->assertTrue($entry->created_at->lte(now()));
                $this->assertSame($index === 0 ? null : $history[$index - 1]->status, $entry->previous_status);
                if ($index > 0) {
                    $this->assertContains($entry->status, config('kasus.transitions.'.$entry->previous_status));
                }
            }
            $this->assertSame($case->current_status, $history->last()->status);
            $started = $history->firstWhere('status', 'dalam_pelaksanaan');
            foreach ($case->progress as $progress) {
                $this->assertTrue($assignment->accepted_at->lte($started->created_at));
                $this->assertTrue($started->created_at->lte($progress->created_at));
                $this->assertTrue($progress->created_at->lte(now()));
                $this->assertSame($assignment->popt_id, $progress->actor_id);
            }
            if ($case->current_status === 'selesai') {
                $report = $case->finalReport;
                $this->assertNotNull($report);
                $this->assertTrue($case->progress->max('created_at')->lte($report->submitted_at));
                $this->assertTrue($report->submitted_at->lte($case->completed_at));
                $this->assertTrue($case->completed_at->lte(now()));
                $this->assertSame($case->completed_at->toDateTimeString(), $history->last()->created_at->toDateTimeString());
                $this->assertSame('selesai', $assignment->status);
                $this->assertNull($case->penugasanAktif);
            }
        }
        $early = $this->case('017');
        $late = $this->case('018');
        $this->assertTrue($early->completed_at->lt($early->penugasanTerakhir->deadline_at));
        $this->assertTrue($late->completed_at->gt($late->penugasanTerakhir->deadline_at));
        $this->assertFalse(app(MonitoringStatusService::class)->resolve($late)['is_overdue']);
    }

    public function test_pending_approved_and_rejected_extensions_use_the_right_effective_target(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        foreach (['013' => 'pending', '014' => 'approved', '015' => 'rejected'] as $id => $expected) {
            $case = $this->case($id);
            $extension = $case->extensionRequests->first();
            $this->assertSame($expected, $extension->status);
            $this->assertSame($case->penugasanAktif->popt_id, $extension->requested_by);
            $this->assertTrue($extension->created_at->lt($extension->current_deadline_at));
            $this->assertTrue($extension->proposed_deadline_at->gt($extension->current_deadline_at));
            $this->assertTrue($case->progress->min('created_at')->lt($extension->created_at));
            $this->assertSame('dalam_pelaksanaan', $case->current_status);
            $monitoring = app(MonitoringStatusService::class)->resolve($case);
            $this->assertSame('dalam_penanganan', $monitoring['key']);
            if ($expected === 'approved') {
                $this->assertTrue($extension->reviewed_at->gt($extension->created_at));
                $this->assertTrue($extension->reviewer->hasRole('operator_uptd'));
                $this->assertTrue($monitoring['effective_deadline_at']->eq($extension->proposed_deadline_at));
                $this->assertFalse($monitoring['is_overdue']);
            } else {
                $this->assertTrue($monitoring['effective_deadline_at']->eq($extension->current_deadline_at));
                $this->assertTrue($monitoring['is_overdue']);
                if ($expected === 'pending') {
                    $this->assertNull($extension->reviewed_at);
                    $this->assertNull($extension->reviewed_by);
                } else {
                    $this->assertNotNull($extension->reviewed_at);
                }
            }
        }
        $this->assertTrue(app(MonitoringStatusService::class)->resolve($this->case('009'))['is_overdue']);
        $this->assertSame('dalam_penanganan', app(MonitoringStatusService::class)->resolve($this->case('009'))['key']);
    }

    public function test_final_reports_have_real_valid_local_pngs_and_public_storage_urls(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        foreach (LaporanAkhirPenanganan::with(['evidences', 'kasus.permohonan.creator'])->get() as $report) {
            foreach (['ringkasan_tindakan', 'hasil_penanganan', 'rekomendasi'] as $field) {
                $this->assertNotEmpty($report->{$field});
            }
            $this->assertCount(2, $report->evidences);
            $this->assertSame($report->kasus->penugasanTerakhir->popt_id, $report->submitted_by);
            $response = $this->actingAs($report->kasus->permohonan->creator)->get(route('permohonan.show', $report->kasus->permohonan_id))->assertOk();
            foreach ($report->evidences as $evidence) {
                Storage::disk('public')->assertExists($evidence->file_path);
                $image = getimagesize(Storage::disk('public')->path($evidence->file_path));
                $this->assertSame('image/png', $image['mime']);
                $this->assertGreaterThanOrEqual(600, $image[0]);
                $file = new UploadedFile(Storage::disk('public')->path($evidence->file_path), $evidence->file_name, 'image/png', null, true);
                $this->assertTrue(Validator::make(['file' => $file], ['file' => app(LaporanAkhirEvidenceService::class)->validationRules()])->passes());
                $url = PublicStorageUrl::make($evidence->file_path);
                $this->assertStringContainsString('/storage/laporan-akhir/demo-status-v2/', $url);
                $response->assertSee($url, false);
                $this->assertTrue($report->submitted_at->lte($evidence->created_at));
                $this->assertTrue($evidence->created_at->lte($report->kasus->completed_at));
            }
        }
    }

    public function test_webgis_report_kpis_filters_and_read_only_access_match_the_seeded_statuses(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        $leader = User::where('email', 'pimpinan.tester@sipakarbun.local')->firstOrFail();
        Sanctum::actingAs($leader);
        $data = collect($this->getJson('/api/monitoring/kasus')->assertOk()->assertJsonPath('meta.total', 20)->json('data'));
        $this->assertEquals(self::DISTRIBUTION, $data->countBy('monitoring_status')->all());
        $this->assertSame(20, $data->unique('monitoring_id')->count());
        $this->assertSame(5, $data->where('is_overdue', true)->count());
        $this->assertSame(0, $data->where('monitoring_status', 'selesai')->where('is_overdue', true)->count());
        $this->get('/webgis')->assertOk()->assertSee('Melewati Target Waktu');
        $this->get('/laporan-monitoring')->assertOk()->assertViewHas('summary', fn ($summary) => $summary === app(MonitoringCaseService::class)->summary());
        foreach (self::DISTRIBUTION as $status => $count) {
            $this->getJson('/api/monitoring/kasus?status='.$status)->assertOk()->assertJsonPath('meta.total', $count);
            $this->get('/laporan-monitoring?status='.$status)->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total'] === $count);
        }
        $this->getJson('/api/monitoring/kasus?overdue=1')->assertOk()->assertJsonPath('meta.total', 5);
        $this->get('/laporan-monitoring?status=dalam_penanganan&overdue=1')->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total'] === 5);
        $this->get('/laporan-monitoring?regency=KAB+SUMEDANG')->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total'] === 5);
        $this->postJson('/api/popt/kasus/'.$this->case('007')->id.'/start')->assertForbidden();
        $this->deleteJson('/api/kasus/'.$this->case('017')->id)->assertForbidden();
    }

    public function test_poktan_owns_its_group_and_only_its_requests_and_popt_assignments_are_scoped(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        $owner = User::where('email', 'poktan.tester@sipakarbun.local')->firstOrFail();
        $owned = PermohonanPenanganan::where('created_by', $owner->id)->get();
        $other = PermohonanPenanganan::where('created_by', '!=', $owner->id)->firstOrFail();
        $response = $this->actingAs($owner)->get(route('permohonan.index'))->assertOk()->assertDontSee($other->permohonan_code);
        $this->assertCount(5, $owned);
        foreach ($owned as $request) {
            $response->assertSee($request->permohonan_code);
            $this->get(route('permohonan.show', $request->id))->assertOk();
        }
        $this->get(route('permohonan.show', $other->id))->assertNotFound();
        Sanctum::actingAs($owner);
        $this->getJson('/api/permohonan/'.$other->id)->assertNotFound();
        $this->getJson('/api/monitoring/kasus')->assertForbidden();
        $popt = User::where('email', 'popt.tester@sipakarbun.local')->firstOrFail();
        $this->actingAs($popt)->get(route('popt.penugasan.show', $this->case('007')->id))->assertOk()->assertSee('Terima Penugasan');
        Sanctum::actingAs($popt);
        $this->postJson('/api/popt/kasus/'.$this->case('009')->id.'/accept')->assertForbidden();
    }

    public function test_reseed_preserves_passwords_dates_history_reports_and_archived_cases(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        $user = User::where('email', 'poktan.tester@sipakarbun.local')->firstOrFail();
        $user->update(['password' => Hash::make('Password-Demo-Yang-Sudah-Diubah!')]);
        $this->case('017')->delete();
        $tables = ['users', 'ref_komoditas', 'ref_kelompok_tani', 'diagnoses', 'diagnosis_symptoms', 'diagnosis_results', 'permohonan_penanganan', 'keputusan_permohonan', 'kasus_penanganan', 'penugasan_popt', 'riwayat_status_penanganan', 'progres_penanganan', 'perpanjangan_penugasan', 'laporan_akhir_penanganan', 'laporan_akhir_evidences'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }
        $this->travel(30)->days();
        config(['services.uat.demo_password' => '']);
        $this->seed(SipakarbunDemoSeeder::class);
        foreach ($tables as $table) {
            $this->assertEquals($before[$table], DB::table($table)->orderBy('id')->get()->toArray(), $table.' berubah saat reseed.');
        }
        $this->assertTrue(Hash::check('Password-Demo-Yang-Sudah-Diubah!', $user->fresh()->password));
        $this->assertTrue(KasusPenanganan::withTrashed()->where('kasus_code', 'KS-DEMO-V2-017')->firstOrFail()->trashed());
    }

    public function test_all_demo_accounts_can_login_with_initial_configured_password(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);
        foreach (User::all() as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertRedirect();
            $this->assertAuthenticatedAs($user);
            $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
            $this->post('/logout')->assertRedirect(route('login'));
        }
        $this->assertDatabaseMissing('users', ['password' => self::PASSWORD]);
    }

    public function test_seeder_refuses_mixed_database_before_modifying_users_or_references(): void
    {
        $user = User::factory()->create(['email' => 'user-existing@example.com']);
        RefKelompokTani::create(['source' => 'disbun', 'disbun_record_id' => '6021', 'nama' => 'Kelompok Tani Existing', 'is_verified' => true, 'sync_status' => 'synced']);
        $passwordHash = $user->password;
        $referencesBefore = RefKomoditas::orderBy('id')->get()->toArray();
        try {
            $this->seed(SipakarbunDemoSeeder::class);
            $this->fail('Database campuran harus ditolak.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('data non-demo', $exception->getMessage());
        }
        $this->assertSame(1, User::count());
        $this->assertSame(1, RefKelompokTani::count());
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertSame($referencesBefore, RefKomoditas::orderBy('id')->get()->toArray());
        $this->assertSame(0, PermohonanPenanganan::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_missing_new_account_password_rolls_back_the_seed(): void
    {
        config(['services.uat.demo_password' => '']);
        $referencesBefore = RefKomoditas::orderBy('id')->get()->toArray();
        try {
            $this->seed(SipakarbunDemoSeeder::class);
            $this->fail('Password akun baru wajib diisi.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SIPAKARBUN_DEMO_PASSWORD', $exception->getMessage());
        }
        $this->assertSame(0, User::count());
        $this->assertSame($referencesBefore, RefKomoditas::orderBy('id')->get()->toArray());
        $this->assertSame(0, PermohonanPenanganan::count());
    }

    public function test_disbun_reference_alone_is_enough_to_refuse_the_seed(): void
    {
        RefKelompokTani::create(['source' => 'disbun', 'disbun_record_id' => '6021', 'nama' => 'Referensi Existing', 'is_verified' => true, 'sync_status' => 'synced']);
        $before = RefKelompokTani::firstOrFail()->getAttributes();
        try {
            $this->seed(SipakarbunDemoSeeder::class);
            $this->fail('Referensi di luar namespace demo harus dilindungi.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('data non-demo', $exception->getMessage());
        }
        $this->assertSame($before, RefKelompokTani::firstOrFail()->getAttributes());
        $this->assertSame(0, User::count());
        $this->assertSame(0, PermohonanPenanganan::count());
    }

    public function test_demo_seeder_refuses_staging(): void
    {
        app()->detectEnvironment(fn () => 'staging');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('local/testing');
        app(SipakarbunDemoSeeder::class)->run();
    }

    public function test_existing_primary_demo_password_is_not_replaced_during_first_seed(): void
    {
        $this->seed(RoleSeeder::class);
        $existing = User::factory()->create(['email' => 'admin.tester@sipakarbun.local', 'password' => Hash::make('Existing-Admin-Demo!')]);
        $existing->assignRole('admin');
        $passwordHash = $existing->password;
        $this->seed(SipakarbunDemoSeeder::class);
        $this->assertSame($passwordHash, $existing->fresh()->password);
        $this->assertTrue(Hash::check('Existing-Admin-Demo!', $existing->fresh()->password));
    }

    public function test_demo_seeder_refuses_production(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak boleh dijalankan pada environment production');
        app(SipakarbunDemoSeeder::class)->run();
    }

    #[DataProvider('demoDates')]
    public function test_relative_dates_keep_distribution_and_overdue_consistent_on_different_demo_days(string $date): void
    {
        $this->travelTo(now()->parse($date));
        $this->seed(SipakarbunDemoSeeder::class);
        $summary = app(MonitoringCaseService::class)->summary();
        $this->assertSame(self::DISTRIBUTION, $summary['by_status']);
        $this->assertSame(5, $summary['overdue']);
        $this->assertSame(4, $summary['completed']);
    }

    public static function demoDates(): array
    {
        return [['2027-03-20 09:30:00'], ['2028-12-31 23:30:00']];
    }

    private function case(string $id): KasusPenanganan
    {
        return KasusPenanganan::where('kasus_code', 'KS-DEMO-V2-'.$id)->firstOrFail();
    }
}
