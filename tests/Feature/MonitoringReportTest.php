<?php

namespace Tests\Feature;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_pimpinan_dan_admin_dapat_membuka_laporan_monitoring(): void
    {
        $this->actingAs($this->makeUser('pimpinan'))
            ->get('/laporan-monitoring')
            ->assertOk()
            ->assertSee('Laporan Monitoring')
            ->assertSee('Tabel Laporan Monitoring');

        auth()->logout();

        $this->actingAs($this->makeUser('admin'))
            ->get('/laporan-monitoring')
            ->assertOk();
    }

    public function test_role_lain_tidak_dapat_membuka_laporan_monitoring(): void
    {
        foreach (['operator_uptd', 'popt', 'poktan'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/laporan-monitoring')
                ->assertForbidden();
            auth()->logout();
        }
    }

    public function test_filter_status_komoditas_wilayah_penyakit_dan_periode(): void
    {
        $this->makeCase([
            'case_code' => 'KS-REPORT-MATCH',
            'status' => KasusPenanganan::STATUS_SELESAI,
            'commodity' => 'Kopi Arabika',
            'disease' => 'Karat Daun Kopi',
            'regency' => 'Kabupaten Garut',
            'created_at' => '2026-01-15 10:00:00',
        ]);
        $this->makeCase([
            'case_code' => 'KS-REPORT-OTHER',
            'status' => KasusPenanganan::STATUS_DITUNDA,
            'commodity' => 'Kakao',
            'disease' => 'Busuk Buah Kakao',
            'regency' => 'Kabupaten Bandung',
            'created_at' => '2026-02-15 10:00:00',
        ]);

        $this->actingAs($this->makeUser('pimpinan'));

        $response = $this->get('/laporan-monitoring?status=selesai&commodity=Kopi+Arabika&regency=Kabupaten+Garut&disease=Karat+Daun+Kopi&date_from=2026-01-01&date_to=2026-01-31')
            ->assertOk();

        $response->assertSee('KS-REPORT-MATCH')
            ->assertDontSee('KS-REPORT-OTHER')
            ->assertSee('>1<', false)
            ->assertSee('>0<', false);
    }

    public function test_soft_deleted_case_does_not_appear_and_empty_state_is_clear(): void
    {
        $case = $this->makeCase(['case_code' => 'KS-REPORT-DELETED']);
        $case->delete();

        $this->actingAs($this->makeUser('pimpinan'));

        $this->get('/laporan-monitoring')
            ->assertOk()
            ->assertDontSee('KS-REPORT-DELETED')
            ->assertSee('Tidak ada data monitoring yang sesuai dengan filter.');
    }

    public function test_laporan_menampilkan_popt_dan_fallback_belum_ditugaskan(): void
    {
        $popt = $this->makeUser('popt', 'POPT Laporan');
        $this->makeCase(['case_code' => 'KS-REPORT-ASSIGNED', 'popt' => $popt]);
        $this->makeCase(['case_code' => 'KS-REPORT-UNASSIGNED']);

        $this->actingAs($this->makeUser('pimpinan'));

        $this->get('/laporan-monitoring')
            ->assertOk()
            ->assertSee('POPT Laporan')
            ->assertSee('Belum ditugaskan')
            ->assertDontSee('Hapus Kasus')
            ->assertDontSee('Simpan Penugasan');
    }

    public function test_pagination_mempertahankan_filter(): void
    {
        for ($index = 1; $index <= 16; $index++) {
            $this->makeCase([
                'case_code' => 'KS-REPORT-PAGE-'.$index,
                'commodity' => 'Kopi Arabika',
                'regency' => 'Kabupaten Garut',
            ]);
        }

        $this->makeCase([
            'case_code' => 'KS-REPORT-PAGE-OTHER',
            'commodity' => 'Kakao',
            'regency' => 'Kabupaten Bandung',
        ]);

        $this->actingAs($this->makeUser('pimpinan'));

        $this->get('/laporan-monitoring?commodity=Kopi+Arabika&page=2')
            ->assertOk()
            ->assertSee('commodity=Kopi%20Arabika', false)
            ->assertDontSee('KS-REPORT-PAGE-OTHER');
    }

    private function makeUser(string $role, string $name = 'Report User'): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function makeCase(array $attributes = []): KasusPenanganan
    {
        $owner = $this->makeUser('poktan', 'Poktan Laporan');
        $permohonan = PermohonanPenanganan::factory()->create([
            'kelompok_tani_name_snapshot' => $attributes['kelompok'] ?? 'Poktan Laporan',
            'kabupaten' => $attributes['regency'] ?? 'Kabupaten Garut',
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
            'created_by' => $owner->id,
        ]);

        $case = KasusPenanganan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'kasus_code' => $attributes['case_code'] ?? 'KS-REPORT-'.fake()->unique()->numberBetween(1000, 9999),
            'current_status' => $attributes['status'] ?? KasusPenanganan::STATUS_DITERIMA,
            'komoditas_name_snapshot' => $attributes['commodity'] ?? 'Kopi Arabika',
            'penyakit_name_snapshot' => $attributes['disease'] ?? 'Karat Daun Kopi',
            'created_by' => $owner->id,
        ]);

        if (! empty($attributes['created_at'])) {
            $case->forceFill(['created_at' => Carbon::parse($attributes['created_at'])])->saveQuietly();
        }

        if (($attributes['popt'] ?? null) instanceof User) {
            PenugasanPopt::factory()->create([
                'kasus_id' => $case->id,
                'popt_id' => $attributes['popt']->id,
                'assigned_by' => $owner->id,
            ]);
        }

        return $case->fresh();
    }
}
