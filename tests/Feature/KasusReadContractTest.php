<?php

namespace Tests\Feature;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KasusReadContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'operator_uptd', 'popt', 'pimpinan', 'poktan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_admin_mendapatkan_required_read_fields_dan_history(): void
    {
        $admin = $this->buatUser('admin');
        $operator = $this->buatUser('operator_uptd');
        $popt = $this->buatUser('popt', 'Budi');
        $kasus = $this->buatKasus($this->buatUser('poktan'), $operator, $popt);

        RiwayatStatusPenanganan::query()->where('kasus_id', $kasus->id)->delete();
        RiwayatStatusPenanganan::factory()->create([
            'kasus_id' => $kasus->id,
            'previous_status' => KasusPenanganan::STATUS_DITUGASKAN,
            'status' => KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            'catatan' => 'Review teknis dimulai.',
            'actor_id' => $popt->id,
            'created_at' => Carbon::parse('2026-08-21 08:00:00'),
        ]);
        RiwayatStatusPenanganan::factory()->create([
            'kasus_id' => $kasus->id,
            'previous_status' => KasusPenanganan::STATUS_SEDANG_DIREVIEW,
            'status' => KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
            'catatan' => 'Penanganan sedang dilaksanakan.',
            'actor_id' => $popt->id,
            'created_at' => Carbon::parse('2026-08-21 10:15:00'),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/kasus')
            ->assertOk()
            ->assertJsonPath('data.0.kasus_id', $kasus->id)
            ->assertJsonPath('data.0.latitude_kasus', -6.9123)
            ->assertJsonPath('data.0.longitude_kasus', 107.6123)
            ->assertJsonPath('data.0.kelompok_tani.id', 101)
            ->assertJsonPath('data.0.kelompok_tani.nama', 'Poktan Maju Bersama')
            ->assertJsonPath('data.0.komoditas.kode', 'KP-045')
            ->assertJsonPath('data.0.penyakit.nama', 'Jamur Akar Putih')
            ->assertJsonPath('data.0.wilayah.kode_kabupaten', '3201')
            ->assertJsonPath('data.0.wilayah.kecamatan', 'Leuwiliang')
            ->assertJsonPath('data.0.request_status', PermohonanPenanganan::STATUS_DITERIMA)
            ->assertJsonPath('data.0.current_status', KasusPenanganan::STATUS_DALAM_PELAKSANAAN)
            ->assertJsonPath('data.0.handling_status', KasusPenanganan::STATUS_DALAM_PELAKSANAAN)
            ->assertJsonPath('data.0.penugasan_popt.id', $popt->id)
            ->assertJsonPath('data.0.penugasan_popt.nama', 'Budi')
            ->assertJsonPath('data.0.last_note', 'Penanganan sedang dilaksanakan.')
            ->assertJsonPath('data.0.riwayat_status.0.note', 'Penanganan sedang dilaksanakan.')
            ->assertJsonPath('data.0.riwayat_status.1.note', 'Review teknis dimulai.');
    }

    public function test_operator_dan_pimpinan_mendapatkan_read_global_tanpa_mutation(): void
    {
        $operator = $this->buatUser('operator_uptd');
        $pimpinan = $this->buatUser('pimpinan');
        $kasus = $this->buatKasus($this->buatUser('poktan'), $operator);

        foreach ([$operator, $pimpinan] as $reader) {
            Sanctum::actingAs($reader);

            $this->getJson('/api/kasus')
                ->assertOk()
                ->assertJsonPath('data.0.kasus_id', $kasus->id);
        }

        Sanctum::actingAs($pimpinan);
        $this->postJson("/api/kasus/{$kasus->id}/assign-popt", ['popt_id' => $operator->id])
            ->assertForbidden();
    }

    public function test_popt_hanya_melihat_kasus_yang_ditugaskan(): void
    {
        $operator = $this->buatUser('operator_uptd');
        $poptA = $this->buatUser('popt', 'POPT A');
        $poptB = $this->buatUser('popt', 'POPT B');
        $owner = $this->buatUser('poktan');
        $kasusA = $this->buatKasus($owner, $operator, $poptA);
        $kasusB = $this->buatKasus($owner, $operator, $poptB);

        Sanctum::actingAs($poptA);

        $this->getJson('/api/kasus')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kasus_id', $kasusA->id);

        $this->getJson("/api/kasus/{$kasusB->id}")->assertForbidden();
        $this->getJson("/api/kasus/{$kasusB->id}/history")->assertForbidden();
        $this->postJson("/api/kasus/{$kasusB->id}/assign-popt", ['popt_id' => $poptA->id])
            ->assertForbidden();
    }

    public function test_kasus_dengan_status_diterima_memisahkan_request_dan_handling(): void
    {
        $admin = $this->buatUser('admin');
        $kasus = $this->buatKasus($this->buatUser('poktan'), $this->buatUser('operator_uptd'));

        Sanctum::actingAs($admin);

        $this->getJson('/api/kasus')
            ->assertOk()
            ->assertJsonPath('data.0.request_status', PermohonanPenanganan::STATUS_DITERIMA)
            ->assertJsonPath('data.0.current_status', KasusPenanganan::STATUS_DITERIMA)
            ->assertJsonPath('data.0.handling_status', KasusPenanganan::STATUS_DITERIMA)
            ->assertJsonPath('data.0.penugasan_popt', null);
    }

    public function test_guest_tidak_dapat_membaca_contract(): void
    {
        $this->getJson('/api/kasus')->assertUnauthorized();
    }

    public function test_web_session_dapat_membaca_contract_dari_same_origin_api(): void
    {
        config(['session.driver' => 'database']);
        $this->assertContains(
            EnsureFrontendRequestsAreStateful::class,
            app('router')->getMiddlewareGroups()['api'],
        );

        $admin = $this->buatUser('admin');

        $login = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $sessionCookie = $login->getCookie(config('session.cookie'));
        Auth::forgetGuards();
        app('session')->forgetDrivers();

        $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
            ->withHeader('Referer', 'http://localhost:8000/webgis')
            ->getJson('/api/kasus')
            ->assertOk();
    }

    public function test_koordinat_kasus_tidak_fallback_ke_lokasi_poktan(): void
    {
        $admin = $this->buatUser('admin');
        $permohonan = PermohonanPenanganan::factory()->create([
            'kelompok_tani_id' => 101,
            'kelompok_tani_name_snapshot' => 'Poktan Dengan Koordinat Referensi',
            'latitude_kasus' => null,
            'longitude_kasus' => null,
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
        ]);
        $kasus = KasusPenanganan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'latitude_kasus' => null,
            'longitude_kasus' => null,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/kasus')
            ->assertOk()
            ->assertJsonPath('data.0.kasus_id', $kasus->id)
            ->assertJsonPath('data.0.latitude_kasus', null)
            ->assertJsonPath('data.0.longitude_kasus', null)
            ->assertJsonPath('data.0.lokasi_kasus.latitude', null)
            ->assertJsonPath('data.0.lokasi_kasus.longitude', null);
    }

    private function buatUser(string $role, ?string $name = null): User
    {
        $user = User::factory()->create($name === null ? [] : ['name' => $name]);
        $user->assignRole($role);

        return $user;
    }

    private function buatKasus(User $owner, User $operator, ?User $popt = null): KasusPenanganan
    {
        $permohonan = PermohonanPenanganan::factory()->create([
            'kelompok_tani_id' => 101,
            'kelompok_tani_name_snapshot' => 'Poktan Maju Bersama',
            'latitude_kasus' => -6.9123,
            'longitude_kasus' => 107.6123,
            'kode_kabupaten' => '3201',
            'kabupaten' => 'Kabupaten Bogor',
            'kode_kecamatan' => '320114',
            'kecamatan' => 'Leuwiliang',
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
            'created_by' => $owner->id,
        ]);
        $kasus = KasusPenanganan::factory()->create([
            'permohonan_id' => $permohonan->id,
            'current_status' => $popt === null
                ? KasusPenanganan::STATUS_DITERIMA
                : KasusPenanganan::STATUS_DALAM_PELAKSANAAN,
            'komoditas_id' => 5,
            'komoditas_code_snapshot' => 'KP-045',
            'komoditas_name_snapshot' => 'Karet',
            'penyakit_id' => 4,
            'penyakit_name_snapshot' => 'Jamur Akar Putih',
            'latitude_kasus' => -6.9123,
            'longitude_kasus' => 107.6123,
            'created_by' => $operator->id,
        ]);

        RiwayatStatusPenanganan::factory()->create([
            'kasus_id' => $kasus->id,
            'status' => $kasus->current_status,
            'catatan' => 'Status terakhir.',
            'actor_id' => $operator->id,
        ]);

        if ($popt !== null) {
            PenugasanPopt::factory()->create([
                'kasus_id' => $kasus->id,
                'popt_id' => $popt->id,
                'assigned_by' => $operator->id,
            ]);
        }

        return $kasus;
    }
}
