<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\KasusPenanganan;
use App\Models\LaporanAkhirEvidence;
use App\Models\LaporanAkhirPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\PerpanjanganPenugasan;
use App\Models\ProgresPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PoktanHandlingVisibilityPhase5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'operator_uptd', 'popt', 'admin', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @return array{0: PermohonanPenanganan, 1: KasusPenanganan, 2: User} */
    private function caseFor(User $poktan, string $status, ?string $deadline = null): array
    {
        $operator = $this->user('operator_uptd');
        $popt = $this->user('popt');
        $permohonan = PermohonanPenanganan::factory()->diterima()->create([
            'created_by' => $poktan->id,
            'created_at' => now()->subDays(5),
        ]);
        $kasus = KasusPenanganan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'current_status' => $status,
        ]);

        if ($deadline !== null) {
            PenugasanPopt::factory()->create([
                'kasus_id' => $kasus->id,
                'popt_id' => $popt->id,
                'assigned_by' => $operator->id,
                'deadline_at' => $deadline,
                'accepted_at' => $status !== KasusPenanganan::STATUS_DITUGASKAN ? now()->subDays(2) : null,
            ]);
        }

        return [$permohonan, $kasus, $popt];
    }

    public function test_accepted_case_without_assignment_shows_waiting_state(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan] = $this->caseFor($poktan, KasusPenanganan::STATUS_DITERIMA);

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Menunggu Penanganan')
            ->assertSee('Belum ditugaskan')
            ->assertSee('Belum tersedia');
    }

    public function test_assigned_but_not_accepted_shows_popt_and_waiting_copy(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan, , $popt] = $this->caseFor($poktan, KasusPenanganan::STATUS_DITUGASKAN, now()->addDays(3)->toDateTimeString());

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Menunggu Penanganan')
            ->assertSee($popt->name)
            ->assertSee('Menunggu POPT menerima penugasan.')
            ->assertSee('Tepat Waktu');
    }

    public function test_in_progress_does_not_expose_technical_status_as_primary(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan] = $this->caseFor($poktan, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, now()->addDays(3)->toDateTimeString());

        $response = $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))->assertOk();

        $response->assertSee('Dalam Penanganan')
            ->assertDontSee('Dalam Pelaksanaan')
            ->assertSee('Informasi Penanganan');
    }

    public function test_progress_is_rendered_as_read_only_timeline(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan, $kasus, $popt] = $this->caseFor($poktan, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, now()->addDays(3)->toDateTimeString());
        ProgresPenanganan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $kasus->penugasanAktif->id,
            'actor_id' => $popt->id,
            'catatan' => 'Pemeriksaan kondisi tanaman telah dilakukan.',
            'created_at' => now()->subDay(),
        ]);
        ProgresPenanganan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $kasus->penugasanAktif->id,
            'actor_id' => $popt->id,
            'catatan' => 'Tindakan pengendalian telah dilakukan.',
        ]);

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Perkembangan Penanganan')
            ->assertSee('Pemeriksaan kondisi tanaman telah dilakukan.')
            ->assertSee('Tindakan pengendalian telah dilakukan.')
            ->assertDontSee('Tambah Progress')
            ->assertDontSee('Ajukan Perpanjangan')
            ->assertDontSee('Selesaikan Penanganan');
    }

    public function test_empty_progress_has_clear_message(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan] = $this->caseFor($poktan, KasusPenanganan::STATUS_SEDANG_DIREVIEW, now()->addDays(3)->toDateTimeString());

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Belum ada update progress dari POPT.');
    }

    public function test_monitoring_status_covers_overdue_and_postponed_cases(): void
    {
        $poktan = $this->user('poktan');
        [$overdue] = $this->caseFor($poktan, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, now()->subDay()->toDateTimeString());
        [$postponed] = $this->caseFor($poktan, KasusPenanganan::STATUS_DITUNDA, now()->addDay()->toDateTimeString());
        [$postponedOverdue] = $this->caseFor($poktan, KasusPenanganan::STATUS_DITUNDA, now()->subDay()->toDateTimeString());

        $this->actingAs($poktan)->get(route('permohonan.show', $overdue->id))->assertOk()->assertSee('Melewati Batas Waktu');
        $this->get(route('permohonan.show', $postponed->id))->assertOk()->assertSee('Ditunda');
        $this->get(route('permohonan.show', $postponedOverdue->id))->assertOk()->assertSee('Melewati Batas Waktu');
    }

    public function test_extension_history_uses_friendly_labels_and_effective_deadline(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan, $kasus] = $this->caseFor($poktan, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, now()->addDays(3)->toDateTimeString());
        $assignment = $kasus->penugasanAktif;
        $oldDeadline = $assignment->deadline_at;
        $newDeadline = now()->addDays(7);

        PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $assignment->popt_id,
            'current_deadline_at' => $oldDeadline,
            'proposed_deadline_at' => $newDeadline,
            'reason' => 'Observasi tambahan diperlukan.',
            'status' => PerpanjanganPenugasan::STATUS_PENDING,
        ]);

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Menunggu Persetujuan')
            ->assertSee('Observasi tambahan diperlukan.')
            ->assertSee($oldDeadline->format('d'))
            ->assertSee($oldDeadline->format('m'));

        $assignment->update(['deadline_at' => $newDeadline]);
        $extension = $kasus->extensionRequests()->first();
        $extension->update(['status' => PerpanjanganPenugasan::STATUS_APPROVED, 'reviewed_at' => now()]);

        $this->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertSee($newDeadline->format('d'));
    }

    public function test_rejected_extension_keeps_current_deadline_and_hides_internal_review_note(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan, $kasus] = $this->caseFor($poktan, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, now()->addDays(3)->toDateTimeString());
        $assignment = $kasus->penugasanAktif;
        $extension = PerpanjanganPenugasan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'requested_by' => $assignment->popt_id,
            'current_deadline_at' => $assignment->deadline_at,
            'proposed_deadline_at' => now()->addDays(7),
            'reason' => 'Alasan terbuka untuk Poktan.',
            'status' => PerpanjanganPenugasan::STATUS_REJECTED,
            'reviewed_at' => now(),
            'review_note' => 'Catatan internal Operator yang tidak boleh tampil.',
        ]);

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Alasan terbuka untuk Poktan.')
            ->assertDontSee('Catatan internal Operator yang tidak boleh tampil.')
            ->assertSee($assignment->deadline_at->format('d'));
    }

    public function test_completed_case_with_report_shows_report_and_safe_photo_url(): void
    {
        Storage::fake('public');
        $poktan = $this->user('poktan');
        [$permohonan, $kasus] = $this->caseFor($poktan, KasusPenanganan::STATUS_SELESAI, now()->subDay()->toDateTimeString());
        $assignment = $kasus->penugasanAktif;
        $assignment->update(['status' => PenugasanPopt::STATUS_SELESAI]);
        $report = LaporanAkhirPenanganan::create([
            'kasus_id' => $kasus->id,
            'penugasan_popt_id' => $assignment->id,
            'submitted_by' => $assignment->popt_id,
            'ringkasan_tindakan' => 'Pemeriksaan dan tindakan lapangan.',
            'hasil_penanganan' => 'Gejala terkendali.',
            'rekomendasi' => 'Lanjutkan pemantauan rutin.',
            'catatan_tambahan' => 'Kunjungan lanjutan dijadwalkan.',
            'submitted_at' => now()->subHours(2),
        ]);
        Storage::disk('public')->put('laporan-akhir/phase5.jpg', 'image');
        LaporanAkhirEvidence::create([
            'laporan_akhir_id' => $report->id,
            'file_path' => 'laporan-akhir/phase5.jpg',
            'file_name' => 'phase5.jpg',
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $assignment->popt_id,
        ]);

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Hasil Penanganan')
            ->assertSee('Pemeriksaan dan tindakan lapangan.')
            ->assertSee('Dokumentasi Hasil Penanganan')
            ->assertSee(Storage::disk('public')->url('laporan-akhir/phase5.jpg'))
            ->assertDontSee('Upload')
            ->assertDontSee('Reopen');
    }

    public function test_legacy_completed_case_without_report_is_graceful(): void
    {
        $poktan = $this->user('poktan');
        [$permohonan] = $this->caseFor($poktan, KasusPenanganan::STATUS_SELESAI, now()->subDay()->toDateTimeString());

        $this->actingAs($poktan)->get(route('permohonan.show', $permohonan->id))
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Laporan akhir belum tersedia untuk kasus historis ini.')
            ->assertDontSee('Tambah Progress');
    }

    public function test_poktan_can_only_view_own_request_directly(): void
    {
        $owner = $this->user('poktan');
        $other = $this->user('poktan');
        [$permohonan] = $this->caseFor($owner, KasusPenanganan::STATUS_DALAM_PELAKSANAAN, now()->addDays(3)->toDateTimeString());

        $this->actingAs($other)->get(route('permohonan.show', $permohonan->id))->assertNotFound();
    }
}
