<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Diagnosis;
use App\Models\Gejala;
use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\Penyakit;
use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use Database\Seeders\SipakarbunDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SipakarbunDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.uat.demo_password' => 'SIPAKARBUN-Tester-2026!']);
        Storage::fake('public');
    }

    public function test_fresh_database_demo_lengkap_dan_seed_kedua_tidak_menduplikasi(): void
    {
        $this->seed(SipakarbunDemoSeeder::class);

        $this->assertSame(5, User::whereIn('email', [
            'admin.tester@sipakarbun.local', 'operator.tester@sipakarbun.local',
            'popt.tester@sipakarbun.local', 'poktan.tester@sipakarbun.local',
            'pimpinan.tester@sipakarbun.local',
        ])->count());
        $this->assertSame(5, RefKelompokTani::where('source', 'disbun')->count());
        $this->assertSame(41, RefKomoditas::where('source', 'disbun')->count());
        $this->assertSame(4, Penyakit::where('kode', 'like', 'PNY-UAT-%')->count());
        $this->assertSame(24, Gejala::where('kode', 'like', 'G-UAT-%')->count());
        $this->assertSame(24, AturanCf::whereHas('penyakit', fn ($q) => $q->where('kode', 'like', 'PNY-UAT-%'))->count());
        $this->assertSame(5, KasusPenanganan::count());
        $this->assertSame(6, PermohonanPenanganan::where('permohonan_code', 'like', 'PM-20260101-%')->count());
        $this->assertSame(1, RiwayatStatusPenanganan::whereHas('kasus', fn ($q) => $q->where('kasus_code', 'KS-20260101-9001'))->where('status', 'diterima')->count());
        $this->assertSame(6, RiwayatStatusPenanganan::whereHas('kasus', fn ($q) => $q->where('kasus_code', 'KS-20260101-9004'))->count());

        foreach (['admin', 'operator_uptd', 'popt', 'poktan', 'pimpinan'] as $role) {
            $email = $role === 'admin' ? 'admin.tester@sipakarbun.local' : $role.'.tester@sipakarbun.local';
            if ($role === 'operator_uptd') $email = 'operator.tester@sipakarbun.local';
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame([$role], $user->getRoleNames()->values()->all());
            $this->assertTrue(Hash::check('SIPAKARBUN-Tester-2026!', $user->password));
        }

        foreach ([
            'knowledge/penyakit/karat-daun-kopi.svg', 'knowledge/penyakit/busuk-buah-kakao.svg',
            'knowledge/penyakit/bpkc-cengkeh.svg', 'knowledge/penyakit/busuk-pucuk-kelapa.svg',
            'knowledge/gejala/karat-daun-kopi.svg', 'knowledge/gejala/busuk-buah-kakao.svg',
            'knowledge/gejala/bpkc-cengkeh.svg', 'knowledge/gejala/busuk-pucuk-kelapa.svg',
        ] as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertFileExists(database_path('seeders/assets/'.$path));
        }

        $this->seed(SipakarbunDemoSeeder::class);

        $this->assertSame(5, User::where('email', 'like', '%@sipakarbun.local')->count());
        $this->assertSame(5, RefKelompokTani::where('source', 'disbun')->count());
        $this->assertSame(41, RefKomoditas::where('source', 'disbun')->count());
        $this->assertSame(5, KasusPenanganan::count());
        $this->assertSame(6, PermohonanPenanganan::where('permohonan_code', 'like', 'PM-20260101-%')->count());
        $this->assertSame(4, PenugasanPopt::whereHas('kasus', fn ($q) => $q->where('kasus_code', 'like', 'KS-20260101-%'))->count());
        $this->assertSame(1, KasusPenanganan::where('kasus_code', 'KS-20260101-9001')->where('current_status', 'diterima')->count());
        $this->assertSame(1, KasusPenanganan::where('kasus_code', 'KS-20260101-9004')->where('current_status', 'selesai')->count());
        $this->assertSame(1, PermohonanPenanganan::where('permohonan_code', 'PM-20260101-9005')->where('status', 'ditolak')->count());
        $this->assertSame(0, PermohonanPenanganan::where('permohonan_code', 'PM-20260101-9005')->whereHas('kasus')->count());

        $accepted = KasusPenanganan::where('kasus_code', 'KS-20260101-9001')->firstOrFail();
        $this->assertSame(-7.025, (float) $accepted->latitude_kasus);
        $this->assertSame(107.519, (float) $accepted->longitude_kasus);
        $this->assertNotSame((float) RefKelompokTani::where('disbun_record_id', '5488')->value('latitude'), (float) $accepted->latitude_kasus);
        $this->assertNotSame((float) RefKelompokTani::where('disbun_record_id', '5488')->value('longitude'), (float) $accepted->longitude_kasus);

        $admin = User::where('email', 'admin.tester@sipakarbun.local')->firstOrFail();
        $this->actingAs($admin)->getJson('/api/kasus')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_demo_seeder_menolak_environment_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak boleh dijalankan pada environment production');

        app(SipakarbunDemoSeeder::class)->run();
    }
}
