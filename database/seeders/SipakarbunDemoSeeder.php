<?php

namespace Database\Seeders;

use App\Models\AturanCf;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\DiagnosisSymptom;
use App\Models\Gejala;
use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\LaporanAkhirEvidence;
use App\Models\LaporanAkhirPenanganan;
use App\Models\LaporanGejala;
use App\Models\PenugasanPopt;
use App\Models\Penyakit;
use App\Models\PermohonanPenanganan;
use App\Models\PerpanjanganPenugasan;
use App\Models\ProgresPenanganan;
use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use App\Services\CertaintyFactorService;
use App\Services\MockKomoditasReferensiClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Offline fixtures only. Never resets a database or rewrites demo history. */
class SipakarbunDemoSeeder extends Seeder
{
    public const MARKER = 'DATA DEMO SIPAKARBUN V2 — fiktif, bukan laporan resmi Disbun.';

    private const ACCOUNTS = [
        'admin' => ['admin', 'admin.tester@sipakarbun.local', 'Admin Demo SIPAKARBUN'],
        'operator_uptd' => ['operator_uptd', 'operator.tester@sipakarbun.local', 'Operator UPTD Demo'],
        'popt' => ['popt', 'popt.tester@sipakarbun.local', 'POPT Demo'],
        'pimpinan' => ['pimpinan', 'pimpinan.tester@sipakarbun.local', 'Pimpinan Demo'],
        'popt_dua' => ['popt', 'popt.dua.tester@sipakarbun.local', 'POPT Kedua Demo'],
        'kopi' => ['poktan', 'poktan.tester@sipakarbun.local', 'Poktan Kopi Demo'],
        'kopi_garut' => ['poktan', 'poktan.kopi-garut.tester@sipakarbun.local', 'Poktan Kopi Garut Demo'],
        'kakao' => ['poktan', 'poktan.kakao.tester@sipakarbun.local', 'Poktan Kakao Demo'],
        'cengkeh' => ['poktan', 'poktan.cengkeh.tester@sipakarbun.local', 'Poktan Cengkeh Demo'],
        'kelapa' => ['poktan', 'poktan.kelapa.tester@sipakarbun.local', 'Poktan Kelapa Demo'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Seeder demo SIPAKARBUN tidak boleh dijalankan pada environment production.');
        }
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeder demo hanya tersedia pada environment local/testing.');
        }
        $this->assertDemoDatabase();

        DB::transaction(function (): void {
            Model::unguarded(function (): void {
                $this->call(RoleSeeder::class);
                $accounts = $this->seedAccounts();
                $commodities = $this->seedCommodities();
                if (! Penyakit::query()->where('kode', 'like', 'PNY-UAT-%')->exists()) {
                    $this->call(KnowledgeUatSeeder::class);
                }
                $groups = $this->seedGroups($commodities);
                $this->linkAccounts($accounts, $groups);
                $this->seedKnowledgeImages();
                $anchor = CarbonImmutable::now();
                foreach (require database_path('seeders/data/sipakarbun-status-cases.php') as $definition) {
                    $this->seedScenario($definition, $accounts, $groups, $anchor);
                }
            });
        });

        $this->command?->info('Dataset DEMO V2 siap: 20 permohonan, 14 kasus, empat status utama (3 review, 3 ditolak, 10 dalam penanganan, 4 selesai). Password dan histori existing dipertahankan.');
    }

    /** Reject mixed databases before touching accounts, references or files. */
    private function assertDemoDatabase(): void
    {
        $emails = array_column(self::ACCOUNTS, 1);
        $commodityCodes = array_column((new MockKomoditasReferensiClient)->all(), 'kode');
        $foreign = User::query()->whereNotIn('email', $emails)->exists()
            || RefKelompokTani::query()->where('disbun_record_id', 'not like', 'DEMO-SIPAKARBUN-V2-%')->exists()
            || RefKomoditas::query()->where(fn ($q) => $q->whereNotNull('disbun_record_id')->orWhereNotIn('kode', $commodityCodes))->exists()
            || Diagnosis::query()->where(fn ($q) => $q->whereNull('kode')->orWhere('kode', 'not like', 'DG-DEMO-V2-%'))->exists()
            || PermohonanPenanganan::query()->where(fn ($q) => $q->where('permohonan_code', 'not like', 'PM-DEMO-V2-%')->orWhereNull('catatan_pemohon')->orWhere('catatan_pemohon', 'not like', self::MARKER.'%'))->exists()
            || KasusPenanganan::withTrashed()->where('kasus_code', 'not like', 'KS-DEMO-V2-%')->exists()
            || Penyakit::query()->where('kode', 'not like', 'PNY-UAT-%')->exists()
            || Gejala::query()->where('kode', 'not like', 'G-UAT-%')->exists()
            || LaporanGejala::query()->exists();

        if ($foreign) {
            throw new RuntimeException('Database berisi data non-demo atau dataset lama. Gunakan database kosong/terisolasi; tidak ada data yang dihapus atau ditimpa.');
        }
    }

    /** @return array<string, User> */
    private function seedAccounts(): array
    {
        $accounts = [];
        foreach (self::ACCOUNTS as $key => [$role, $email, $name]) {
            $user = User::query()->where('email', $email)->first();
            if ($user === null) {
                $password = (string) config('services.uat.demo_password', '');
                if ($password === '') {
                    throw new RuntimeException('SIPAKARBUN_DEMO_PASSWORD wajib diisi untuk membuat akun demo baru.');
                }
                $user = User::create(['email' => $email, 'name' => $name, 'password' => $password, 'is_active' => true, 'email_verified_at' => now()]);
                $user->assignRole($role);
            } elseif (! $user->hasRole($role)) {
                throw new RuntimeException('Role akun demo existing tidak sesuai: '.$email.'. Periksa akun tanpa mengganti password atau role secara otomatis.');
            }
            $accounts[$key] = $user;
        }

        return $accounts;
    }

    /** Canonical offline reference codes; no invented official Disbun IDs. */
    private function seedCommodities(): array
    {
        $commodities = [];
        foreach ((new MockKomoditasReferensiClient)->all() as $row) {
            $commodities[$row['kode']] = RefKomoditas::firstOrCreate(['kode' => $row['kode']], [
                'nama' => $row['nama'], 'nama_latin' => $row['nama_latin'] ?? null,
                'disbun_record_id' => null, 'source' => RefKomoditas::SOURCE_DISBUN,
                'source_is_active' => true, 'is_verified' => true, 'sync_status' => RefKomoditas::SYNC_SYNCED,
            ]);
        }

        return $commodities;
    }

    private function seedGroups(array $commodities): array
    {
        $definitions = [
            'kopi' => ['KP-079', 'Kopi Sumedang', 'KAB SUMEDANG', 'Wado', 'Cimungkal', '3211', '321101', -7.003, 108.134],
            'kopi_garut' => ['KP-079', 'Kopi Garut', 'KAB GARUT', 'Tarogong Kaler', 'Sukawangi', '3205', '320504', -7.172, 107.823],
            'kakao' => ['KP-017', 'Kakao Bandung', 'KAB BANDUNG', 'Pangalengan', 'Margamulya', '3204', '320401', -7.025, 107.519],
            'cengkeh' => ['KP-016', 'Cengkeh Cianjur', 'KAB CIANJUR', 'Pacet', 'Cipendawa', '3203', '320301', -6.820, 107.140],
            'kelapa' => ['KP-056', 'Kelapa Bogor', 'KAB BOGOR', 'Cibinong', 'Nanggewer', '3201', '320101', -6.580, 106.800],
        ];
        $groups = [];
        foreach ($definitions as $key => [$code, $name, $regency, $district, $village, $regencyCode, $districtCode, $lat, $lng]) {
            $commodity = $commodities[$code];
            $groups[$key] = RefKelompokTani::firstOrCreate([
                'source' => RefKelompokTani::SOURCE_DISBUN,
                'disbun_record_id' => 'DEMO-SIPAKARBUN-V2-'.strtoupper($key),
            ], [
                'kode' => 'DEMO-V2-'.strtoupper($key), 'kode_kelompok' => 'DEMO-V2-'.strtoupper($key),
                'nama' => 'DEMO FIKTIF '.$name, 'ketua' => 'Ketua Demo Fiktif',
                'jenis_komoditi' => $commodity->nama, 'external_commodity_code' => $code,
                'external_commodity_name' => $commodity->nama, 'commodity_ref_id' => $commodity->id,
                'commodity_mapping_status' => 'mapped', 'kabupaten' => $regency, 'kecamatan' => $district,
                'desa' => $village, 'kelurahan' => $village, 'kode_kabupaten' => $regencyCode,
                'kode_kecamatan' => $districtCode, 'latitude' => $lat, 'longitude' => $lng,
                'status' => 'aktif', 'source_is_active' => true, 'is_verified' => true,
                'sync_status' => RefKelompokTani::SYNC_SYNCED,
            ]);
        }

        return $groups;
    }

    private function linkAccounts(array $accounts, array $groups): void
    {
        foreach ($groups as $key => $group) {
            $user = $accounts[$key];
            if ($user->kelompok_tani_id === null) {
                $user->update(['kelompok_tani_id' => $group->id, 'kelompok_tani_kode' => $group->kode, 'kelompok_tani_nama' => $group->nama]);
            } elseif ((int) $user->kelompok_tani_id !== (int) $group->id) {
                throw new RuntimeException('Tautan Kelompok Tani akun demo tidak sesuai: '.$user->email);
            }
        }
    }

    private function seedKnowledgeImages(): void
    {
        $assets = ['karat-daun-kopi', 'busuk-buah-kakao', 'bpkc-cengkeh', 'busuk-pucuk-kelapa'];
        foreach ($assets as $index => $asset) {
            foreach (['penyakit', 'gejala'] as $type) {
                $path = 'knowledge/demo-status-v2/'.$type.'/'.$asset.'.svg';
                $this->copyAsset('knowledge/'.$type.'/'.$asset.'.svg', $path);
                $record = $type === 'penyakit'
                    ? Penyakit::where('kode', sprintf('PNY-UAT-%03d', $index + 1))->firstOrFail()
                    : Gejala::where('kode', sprintf('G-UAT-%03d', $index * 6 + 1))->firstOrFail();
                if ($record->image_path === null) {
                    $record->update(['image_path' => $path]);
                }
            }
        }
    }

    private function copyAsset(string $source, string $destination): void
    {
        $path = database_path('seeders/assets/'.$source);
        if (! is_file($path)) {
            throw new RuntimeException('Asset demo tidak ditemukan: '.$path);
        }
        $content = file_get_contents($path);
        if (! Storage::disk('public')->exists($destination)
            && ! Storage::disk('public')->put($destination, $content)) {
            throw new RuntimeException('Asset demo gagal disimpan: '.$destination);
        }
    }

    private function seedScenario(array $definition, array $accounts, array $groups, CarbonImmutable $anchor): void
    {
        $id = $definition['id'];
        if (PermohonanPenanganan::where('permohonan_code', 'PM-DEMO-V2-'.$id)->exists()) {
            // Preserve accepted decisions, append-only progress, immutable final
            // reports, archived cases, deadlines and original seed timestamps.
            return;
        }
        $group = $groups[$definition['group']];
        $owner = $accounts[$definition['group']];
        $operator = $accounts['operator_uptd'];
        $popt = $accounts[$definition['popt'] ?? 'popt'];
        $submitted = $anchor->subDays($definition['age']);
        $reviewed = $submitted->addDay()->addHour();
        $decided = $reviewed->addHours(3);
        // New fixtures follow atomic approval/assignment. Existing fixtures
        // are skipped above: never rewrite their historical assignment times.
        $assigned = $decided;
        $accepted = $assigned->addHours(2);
        $started = $accepted->addHour();
        $finished = $submitted->addDays(10);
        $done = ($definition['case_status'] ?? null) === 'selesai';
        $working = in_array($definition['case_status'] ?? null, ['dalam_pelaksanaan', 'selesai'], true);
        $lat = round((float) $group->latitude - 0.003 - (int) $id * 0.0005, 7);
        $lng = round((float) $group->longitude + 0.004 + (int) $id * 0.0005, 7);
        $commodity = RefKomoditas::findOrFail($group->commodity_ref_id);
        $diseaseCode = match ($definition['group']) {
            'kopi', 'kopi_garut' => 'PNY-UAT-001', 'kakao' => 'PNY-UAT-002',
            'cengkeh' => 'PNY-UAT-003', 'kelapa' => 'PNY-UAT-004',
        };
        $disease = Penyakit::where('kode', $diseaseCode)->firstOrFail();
        $diagnosis = $this->seedDiagnosis($id, $owner, $commodity, $disease, $submitted->subHours(2));
        $isReviewed = $definition['status'] !== 'diajukan';
        $request = PermohonanPenanganan::create([
            'permohonan_code' => 'PM-DEMO-V2-'.$id, 'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => $group->id, 'kelompok_tani_name_snapshot' => $group->nama,
            'latitude_kasus' => $lat, 'longitude_kasus' => $lng, 'lokasi_dikonfirmasi' => true,
            'lokasi_sama_dengan_poktan' => false, 'alamat_kasus' => self::MARKER.' Petak '.$id.', '.$group->desa,
            'kode_kabupaten' => $group->kode_kabupaten, 'kabupaten' => $group->kabupaten,
            'kode_kecamatan' => $group->kode_kecamatan, 'kecamatan' => $group->kecamatan,
            'kelurahan' => $group->kelurahan, 'catatan_pemohon' => self::MARKER.' '.$definition['scenario'],
            'status' => $definition['status'], 'created_by' => $owner->id,
            'reviewed_by' => $isReviewed ? $operator->id : null, 'reviewed_at' => $isReviewed ? $reviewed : null,
            'created_at' => $submitted, 'updated_at' => $isReviewed ? $reviewed : $submitted,
        ]);
        if (in_array($definition['status'], ['diajukan', 'sedang_direview'], true)) {
            return;
        }
        KeputusanPermohonan::create([
            'permohonan_id' => $request->id, 'keputusan' => $definition['status'],
            'catatan' => self::MARKER.' '.($definition['status'] === 'ditolak' ? $definition['reason'] : 'Lokasi dan gejala simulasi telah diperiksa; permohonan diterima untuk penanganan.'),
            'operator_id' => $operator->id, 'decided_at' => $decided, 'created_at' => $decided, 'updated_at' => $decided,
        ]);
        $request->update(['updated_at' => $decided]);
        if ($definition['status'] === 'ditolak') {
            return;
        }
        $deadline = $done
            ? ($definition['late_completion'] ? $finished->subDay() : $finished->addDays(2))
            : $anchor->addDays($definition['deadline']);
        $case = KasusPenanganan::create([
            'permohonan_id' => $request->id, 'kasus_code' => 'KS-DEMO-V2-'.$id,
            'current_status' => $definition['case_status'], 'created_by' => $operator->id,
            'komoditas_id' => $commodity->id, 'komoditas_code_snapshot' => $commodity->kode, 'komoditas_name_snapshot' => $commodity->nama,
            'penyakit_id' => $disease->id, 'penyakit_kode_snapshot' => $disease->kode, 'penyakit_name_snapshot' => $disease->nama,
            'latitude_kasus' => $lat, 'longitude_kasus' => $lng,
            'completed_at' => $done ? $finished->addMinutes(10) : null,
            'created_at' => $decided, 'updated_at' => $done ? $finished->addMinutes(10) : ($working ? $started : $assigned),
        ]);
        $assignment = PenugasanPopt::create([
            'kasus_id' => $case->id, 'popt_id' => $popt->id, 'assigned_by' => $operator->id,
            'status' => $done ? PenugasanPopt::STATUS_SELESAI : PenugasanPopt::STATUS_AKTIF,
            'catatan' => self::MARKER.' Periksa petak '.$id.' dan catat hasil kunjungan.',
            'assigned_at' => $assigned,
            'accepted_at' => $definition['case_status'] === 'ditugaskan' ? null : $accepted,
            'deadline_at' => $deadline, 'created_at' => $assigned,
            'updated_at' => $done ? $finished->addMinutes(10) : $assigned,
        ]);
        $this->seedHistory($case, $operator, $popt, $decided, $assigned, $accepted, $started, $finished);
        if ($working) {
            foreach (range(1, $done ? 3 : 2) as $visit) {
                $at = $started->addDays($visit);
                $notes = [1 => 'Kunjungan awal: gejala pada petak dicatat dan bagian tanaman terdampak diperiksa.',
                    2 => 'Sanitasi dan pemantauan petak dilakukan bersama kelompok tani; perkembangan gejala dicatat.',
                    3 => 'Evaluasi tindak lanjut dilakukan; hasil pemeriksaan dirangkum untuk laporan akhir.'];
                ProgresPenanganan::create(['kasus_id' => $case->id, 'penugasan_popt_id' => $assignment->id,
                    'actor_id' => $popt->id, 'catatan' => self::MARKER.' '.$notes[$visit], 'created_at' => $at, 'updated_at' => $at]);
            }
        }
        if (isset($definition['extension'])) {
            $this->seedExtension($definition, $case, $assignment, $operator, $popt, $anchor);
        }
        if ($done) {
            $this->seedFinalReport($case, $assignment, $popt, $finished, $disease);
        }
    }

    private function seedDiagnosis(string $id, User $owner, RefKomoditas $commodity, Penyakit $disease, CarbonImmutable $at): Diagnosis
    {
        $diagnosis = Diagnosis::create(['kode' => 'DG-DEMO-V2-'.$id, 'user_id' => $owner->id,
            'commodity_id' => $commodity->id, 'status' => Diagnosis::STATUS_SELESAI, 'created_at' => $at, 'updated_at' => $at]);
        $rules = AturanCf::where('penyakit_id', $disease->id)->where('status', 'aktif')->orderBy('gejala_id')->limit(2)->get();
        if ($rules->count() !== 2) {
            throw new RuntimeException('Dua aturan Knowledge UAT diperlukan untuk diagnosis demo.');
        }
        $engine = app(CertaintyFactorService::class);
        $trace = [];
        foreach ($rules as $index => $rule) {
            $symptom = Gejala::findOrFail($rule->gejala_id);
            $confidence = $index === 0 ? 0.8 : 1.0;
            DiagnosisSymptom::create(['diagnosis_id' => $diagnosis->id, 'symptom_id' => $symptom->id,
                'symptom_name_snapshot' => $symptom->nama, 'cf_user' => $confidence, 'created_at' => $at]);
            $trace[] = ['gejala_id' => $symptom->id, 'gejala_nama' => $symptom->nama, 'cf_user' => $confidence,
                'cf_pakar' => (float) $rule->cf_pakar, 'cf_gejala' => $engine->cfGejala($confidence, (float) $rule->cf_pakar)];
        }
        $solution = $disease->solusi()->where('status', 'aktif')->firstOrFail();
        DiagnosisResult::create(['diagnosis_id' => $diagnosis->id, 'disease_id' => $disease->id,
            'disease_name_snapshot' => $disease->nama, 'ranking' => 1, 'created_at' => $at,
            'solution_snapshot' => [['judul' => $solution->judul, 'deskripsi' => $solution->deskripsi]],
            'trace_snapshot' => $trace, 'cf_value' => $engine->combine(array_column($trace, 'cf_gejala'))]);

        return $diagnosis;
    }

    private function seedHistory(KasusPenanganan $case, User $operator, User $popt, CarbonImmutable $decided, CarbonImmutable $assigned, CarbonImmutable $accepted, CarbonImmutable $started, CarbonImmutable $finished): void
    {
        $states = [['diterima', $decided, $operator->id], ['ditugaskan', $assigned, $operator->id]];
        if ($case->current_status !== 'ditugaskan') {
            $states[] = ['sedang_direview', $accepted, $popt->id];
        }
        if (in_array($case->current_status, ['dalam_pelaksanaan', 'selesai'], true)) {
            $states[] = ['siap_dieksekusi', $started->subMinute(), $popt->id];
            $states[] = ['dalam_pelaksanaan', $started, $popt->id];
        }
        if ($case->current_status === 'selesai') {
            $states[] = ['selesai', $finished->addMinutes(10), $popt->id];
        }
        $previous = null;
        foreach ($states as [$status, $at, $actor]) {
            RiwayatStatusPenanganan::create(['kasus_id' => $case->id, 'previous_status' => $previous, 'status' => $status,
                'actor_id' => $actor, 'catatan' => self::MARKER.' '.config('kasus.labels.'.$status), 'created_at' => $at]);
            $previous = $status;
        }
    }

    private function seedExtension(array $definition, KasusPenanganan $case, PenugasanPopt $assignment, User $operator, User $popt, CarbonImmutable $anchor): void
    {
        $pending = $definition['extension'] === 'pending';
        $at = $anchor->subDays($pending ? 3 : 5);
        $reviewed = $pending ? null : $at->addDay();
        $proposed = $anchor->addDays($definition['proposed_deadline']);
        PerpanjanganPenugasan::create(['kasus_id' => $case->id, 'penugasan_popt_id' => $assignment->id,
            'requested_by' => $popt->id, 'current_deadline_at' => $assignment->deadline_at,
            'proposed_deadline_at' => $proposed, 'reason' => self::MARKER.' '.$definition['reason'],
            'status' => $definition['extension'], 'reviewed_by' => $pending ? null : $operator->id,
            'reviewed_at' => $reviewed, 'review_note' => $pending ? null : ($definition['extension'] === 'approved'
                ? 'DEMO: tambahan waktu pemantauan disetujui.' : 'DEMO: target tetap; jadwalkan kunjungan menggunakan sumber daya yang tersedia.'),
            'created_at' => $at, 'updated_at' => $reviewed ?? $at]);
        if ($definition['extension'] === 'approved') {
            $assignment->update(['deadline_at' => $proposed, 'updated_at' => $reviewed]);
        }
    }

    private function seedFinalReport(KasusPenanganan $case, PenugasanPopt $assignment, User $popt, CarbonImmutable $at, Penyakit $disease): void
    {
        $report = LaporanAkhirPenanganan::create(['kasus_id' => $case->id, 'penugasan_popt_id' => $assignment->id,
            'submitted_by' => $popt->id, 'submitted_at' => $at, 'created_at' => $at, 'updated_at' => $at,
            'ringkasan_tindakan' => self::MARKER.' Petak diperiksa, sanitasi dilakukan, dan hasil kunjungan untuk '.$disease->nama.' dicatat bersama Poktan.',
            'hasil_penanganan' => 'DEMO: pada kunjungan evaluasi, bagian tanaman yang diamati tidak menunjukkan perluasan gejala baru. Catatan ini hanya skenario simulasi.',
            'rekomendasi' => 'DEMO: lanjutkan pencatatan kondisi petak dan konsultasikan perubahan gejala kepada POPT. Bukan rekomendasi teknis untuk kasus nyata.',
            'catatan_tambahan' => 'Seluruh bukti merupakan ilustrasi placeholder lokal berlabel DATA DEMO; bukan foto serangan tanaman nyata.']);
        foreach (['awal', 'akhir'] as $index => $stage) {
            $path = 'laporan-akhir/demo-status-v2/'.$case->kasus_code.'/bukti-'.$stage.'.png';
            $this->copyAsset('laporan-akhir/demo-status-'.$stage.'.png', $path);
            LaporanAkhirEvidence::create(['laporan_akhir_id' => $report->id, 'file_path' => $path,
                'file_name' => 'DATA-DEMO-'.$stage.'.png', 'mime_type' => 'image/png',
                'uploaded_by' => $popt->id, 'created_at' => $at->addSeconds($index + 1)]);
        }
    }
}
