<?php

namespace Tests\Feature;

use App\Models\LaporanAkhirEvidence;
use App\Models\LaporanAkhirPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PerpanjanganPenugasan;
use App\Models\ProgresPenanganan;
use App\Models\User;
use App\Services\LaporanAkhirEvidenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class HandlingWorkflowFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_schema_and_model_casts_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('penugasan_popt', ['accepted_at', 'deadline_at']));
        $this->assertTrue(Schema::hasColumn('kasus_penanganan', 'completed_at'));
        $this->assertTrue(Schema::hasTable('progres_penanganan'));
        $this->assertTrue(Schema::hasTable('perpanjangan_penugasan'));
        $this->assertTrue(Schema::hasTable('laporan_akhir_penanganan'));
        $this->assertTrue(Schema::hasTable('laporan_akhir_evidences'));

        $assignment = PenugasanPopt::factory()->create([
            'accepted_at' => Carbon::parse('2026-09-18 08:00:00'),
            'deadline_at' => Carbon::parse('2026-09-20 17:00:00'),
        ]);
        $case = $assignment->kasus;

        $this->assertInstanceOf(Carbon::class, $assignment->accepted_at);
        $this->assertInstanceOf(Carbon::class, $assignment->deadline_at);

        $case->update(['completed_at' => Carbon::parse('2026-09-21 10:00:00')]);
        $this->assertInstanceOf(Carbon::class, $case->fresh()->completed_at);
    }

    public function test_legacy_assignment_without_deadline_remains_valid(): void
    {
        $assignment = PenugasanPopt::factory()->create([
            'accepted_at' => null,
            'deadline_at' => null,
        ]);

        $this->assertNull($assignment->accepted_at);
        $this->assertNull($assignment->deadline_at);
        $this->assertNull($assignment->fresh()->deadline_at);
    }

    public function test_progress_is_append_only_relation_data(): void
    {
        $assignment = PenugasanPopt::factory()->create();
        $actor = User::factory()->create();

        ProgresPenanganan::create([
            'kasus_id' => $assignment->kasus_id,
            'penugasan_popt_id' => $assignment->id,
            'actor_id' => $actor->id,
            'catatan' => 'Pemeriksaan awal selesai.',
        ]);
        ProgresPenanganan::create([
            'kasus_id' => $assignment->kasus_id,
            'penugasan_popt_id' => $assignment->id,
            'actor_id' => $actor->id,
            'catatan' => 'Tindakan lapangan dimulai.',
        ]);

        $this->assertCount(2, $assignment->fresh()->progress);
        $this->assertSame(2, $assignment->kasus->fresh()->progress()->count());
        $this->assertSame($actor->id, $assignment->kasus->progress()->first()->actor_id);
    }

    public function test_extension_retains_deadlines_and_has_constants_and_relations(): void
    {
        $assignment = PenugasanPopt::factory()->create();
        $requester = User::factory()->create();
        $reviewer = User::factory()->create();

        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $assignment->kasus_id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $requester->id,
            'current_deadline_at' => Carbon::parse('2026-09-20 17:00:00'),
            'proposed_deadline_at' => Carbon::parse('2026-09-22 17:00:00'),
            'reason' => 'Menunggu hasil pengamatan lanjutan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => Carbon::parse('2026-09-19 09:00:00'),
            'review_note' => 'Menunggu keputusan operator.',
        ]);

        $this->assertSame(['pending', 'approved', 'rejected', 'cancelled'], PerpanjanganPenugasan::STATUSES);
        $this->assertSame($assignment->id, $extension->penugasanPopt->id);
        $this->assertSame($requester->id, $extension->requester->id);
        $this->assertSame($reviewer->id, $extension->reviewer->id);
        $this->assertSame($extension->id, $assignment->fresh()->extensionRequests->first()->id);
    }

    public function test_final_report_has_one_case_relation_and_evidences(): void
    {
        $assignment = PenugasanPopt::factory()->create();
        $submitter = User::factory()->create();

        $report = LaporanAkhirPenanganan::create([
            'kasus_id' => $assignment->kasus_id,
            'penugasan_popt_id' => $assignment->id,
            'submitted_by' => $submitter->id,
            'ringkasan_tindakan' => 'Pemeriksaan dan pengendalian dilakukan.',
            'hasil_penanganan' => 'Gejala berkurang.',
            'rekomendasi' => 'Lanjutkan pemantauan.',
            'submitted_at' => Carbon::parse('2026-09-21 10:00:00'),
        ]);

        $evidence = LaporanAkhirEvidence::create([
            'laporan_akhir_id' => $report->id,
            'file_path' => 'laporan-akhir/final.jpg',
            'file_name' => 'final.jpg',
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $submitter->id,
            'created_at' => Carbon::parse('2026-09-21 10:00:00'),
        ]);

        $this->assertSame($report->id, $assignment->kasus->fresh()->finalReport->id);
        $this->assertSame($report->id, $assignment->fresh()->finalReport->id);
        $this->assertSame($submitter->id, $report->submitter->id);
        $this->assertSame($report->id, $evidence->laporanAkhir->id);
        $this->assertCount(1, $report->fresh()->evidences);
    }

    public function test_submitted_final_report_cannot_be_changed_or_deleted(): void
    {
        $assignment = PenugasanPopt::factory()->create();
        $report = LaporanAkhirPenanganan::create([
            'kasus_id' => $assignment->kasus_id,
            'penugasan_popt_id' => $assignment->id,
            'ringkasan_tindakan' => 'Tindakan.',
            'hasil_penanganan' => 'Hasil.',
            'rekomendasi' => 'Rekomendasi.',
            'submitted_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $report->update(['hasil_penanganan' => 'Perubahan tidak diizinkan.']);
    }

    public function test_final_report_evidence_service_reuses_safe_storage_contract(): void
    {
        Storage::fake('public');
        $service = app(LaporanAkhirEvidenceService::class);

        $this->assertSame(
            ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            $service->validationRules(),
        );
        $this->assertSame(1, LaporanAkhirEvidenceService::MIN_REQUIRED_FILES);

        $stored = $service->store(UploadedFile::fake()->image('hasil akhir.jpg'));

        Storage::disk('public')->assertExists($stored['file_path']);
        $this->assertStringStartsWith('laporan-akhir/', $stored['file_path']);
        $this->assertSame('image/jpeg', $stored['mime_type']);
        $this->assertSame('hasil akhir.jpg', $stored['file_name']);
    }
}
