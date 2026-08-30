<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\KasusPenanganan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebOperationalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function permohonan(User $poktan): PermohonanPenanganan
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $poktan->id,
            'commodity_id' => 1,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);
        DiagnosisResult::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'cf_value' => 0.9,
            'ranking' => 1,
        ]);

        return PermohonanPenanganan::factory()->diajukan()->create([
            'diagnosis_id' => $diagnosis->id,
            'created_by' => $poktan->id,
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
        ]);
    }

    public function test_operator_menerima_dan_menugaskan_kasus_melalui_web(): void
    {
        $operator = $this->user('operator_uptd');
        $poktan = $this->user('poktan');
        $popt = $this->user('popt');
        $permohonan = $this->permohonan($poktan);

        $this->actingAs($operator)
            ->post(route('operator.permohonan.accept', $permohonan->id), ['catatan' => 'Layak ditangani'])
            ->assertRedirect(route('operator.permohonan.show', $permohonan->id));

        $kasus = $permohonan->fresh('kasus')->kasus;
        $this->assertSame(KasusPenanganan::STATUS_DITERIMA, $kasus->current_status);

        $this->actingAs($operator)
            ->post(route('operator.kasus.assign', $kasus->id), ['popt_id' => $popt->id, 'catatan' => 'Mohon ditindaklanjuti'])
            ->assertRedirect(route('operator.kasus.show', $kasus->id));

        $this->assertDatabaseHas('penugasan_popt', ['kasus_id' => $kasus->id, 'popt_id' => $popt->id, 'status' => 'aktif']);

        $this->actingAs($popt)->get(route('popt.penugasan'))->assertOk()->assertSee($kasus->kasus_code);
        $this->actingAs($popt)
            ->post(route('popt.penugasan.status', $kasus->id), ['status' => 'sedang_direview', 'catatan' => 'Pemeriksaan dimulai'])
            ->assertRedirect(route('popt.penugasan.show', $kasus->id));

        $this->assertDatabaseHas('kasus_penanganan', ['id' => $kasus->id, 'current_status' => KasusPenanganan::STATUS_SEDANG_DIREVIEW]);
    }

    public function test_popt_web_hanya_melihat_penugasan_miliknya(): void
    {
        $operator = $this->user('operator_uptd');
        $poktan = $this->user('poktan');
        $poptSaya = $this->user('popt');
        $poptLain = $this->user('popt');

        $pertama = $this->permohonan($poktan);
        $kedua = $this->permohonan($poktan);

        $this->actingAs($operator)->post(route('operator.permohonan.accept', $pertama->id));
        $this->actingAs($operator)->post(route('operator.permohonan.accept', $kedua->id));
        $kasusSaya = $pertama->fresh('kasus')->kasus;
        $kasusLain = $kedua->fresh('kasus')->kasus;
        $this->actingAs($operator)->post(route('operator.kasus.assign', $kasusSaya->id), ['popt_id' => $poptSaya->id]);
        $this->actingAs($operator)->post(route('operator.kasus.assign', $kasusLain->id), ['popt_id' => $poptLain->id]);

        $this->actingAs($poptSaya)->get(route('popt.penugasan'))->assertOk()->assertSee($kasusSaya->kasus_code)->assertDontSee($kasusLain->kasus_code);
        $this->actingAs($poptSaya)->get(route('popt.penugasan.show', $kasusLain->id))->assertForbidden();
    }
}
