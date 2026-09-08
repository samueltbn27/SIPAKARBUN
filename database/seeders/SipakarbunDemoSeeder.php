<?php

namespace Database\Seeders;

use App\Models\AturanCf;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\DiagnosisSymptom;
use App\Models\Gejala;
use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\Penyakit;
use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Reproducible local/demo dataset. This seeder is deliberately explicit and
 * is not called from DatabaseSeeder, so a production seed cannot silently
 * create shared demo accounts or workflow records.
 */
class SipakarbunDemoSeeder extends Seeder
{
    private const CASE_MARKER = 'DATA DEMO SIPAKARBUN — khusus UAT, bukan laporan aktual.';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Seeder demo SIPAKARBUN tidak boleh dijalankan pada environment production.');
        }

        $password = (string) config('services.uat.demo_password', '');
        if ($password === '') {
            throw new RuntimeException('SIPAKARBUN_DEMO_PASSWORD wajib diisi sebelum menjalankan seeder demo.');
        }

        $this->call([RoleSeeder::class, RefKomoditasSeeder::class, KnowledgeUatSeeder::class]);

        $accounts = $this->seedAccounts($password);
        $commodities = $this->seedCommodities();
        $groups = $this->seedGroups($commodities);
        $this->seedKnowledgeImages();
        $this->seedStudyCases($accounts, $commodities, $groups);

        $this->command?->info('SIPAKARBUN demo dataset siap dan dapat dijalankan ulang tanpa duplikasi.');
    }

    /** @return array<string, User> */
    private function seedAccounts(string $password): array
    {
        $definitions = [
            'admin' => ['name' => 'Admin Demo SIPAKARBUN', 'email' => 'admin.tester@sipakarbun.local'],
            'operator_uptd' => ['name' => 'Operator UPTD Demo', 'email' => 'operator.tester@sipakarbun.local'],
            'popt' => ['name' => 'POPT Demo', 'email' => 'popt.tester@sipakarbun.local'],
            'poktan' => ['name' => 'Poktan Demo', 'email' => 'poktan.tester@sipakarbun.local'],
            'pimpinan' => ['name' => 'Pimpinan Demo', 'email' => 'pimpinan.tester@sipakarbun.local'],
        ];

        $accounts = [];
        foreach ($definitions as $role => $definition) {
            $user = User::updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles(Role::findByName($role));
            $accounts[$role] = $user;
        }

        return $accounts;
    }

    /** @return array<string, int> */
    private function seedCommodities(): array
    {
        $ids = [];
        $now = now();

        foreach ((new MockKomoditasReferensiClient)->all() as $row) {
            $existing = RefKomoditas::query()->where('kode', $row['kode'])->first();
            $externalId = $existing?->disbun_record_id ?? (900000 + (int) $row['id']);

            $reference = RefKomoditas::updateOrCreate(
                ['kode' => $row['kode']],
                [
                    'disbun_record_id' => $externalId,
                    'source' => RefKomoditas::SOURCE_DISBUN,
                    'nama' => $row['nama'],
                    'nama_latin' => $row['nama_latin'] ?? null,
                    'source_is_active' => true,
                    'is_verified' => true,
                    'sync_status' => RefKomoditas::SYNC_SYNCED,
                    'quarantine_reason' => null,
                    'last_synced_at' => $now,
                ],
            );

            $ids[$row['kode']] = (int) $reference->id;
        }

        return $ids;
    }

    /** @param array<string, int> $commodities
     *  @return array<string, RefKelompokTani>
     */
    private function seedGroups(array $commodities): array
    {
        $definitions = [
            'anugrah' => [
                'external_id' => '5004', 'code' => '3205042005-004', 'name' => 'ANUGRAH TANI',
                'commodity' => 'KP-138', 'kabupaten' => 'KAB GARUT', 'kecamatan' => 'Tarogong Kaler',
                'desa' => 'Sukawangi', 'latitude' => -7.1719511, 'longitude' => 107.8224774,
                'kode_kabupaten' => '3205', 'kode_kecamatan' => '320504', 'kode_desa' => '3205042005',
            ],
            'arosta' => [
                'external_id' => '5488', 'code' => '3211012001-004', 'name' => 'AROSTA',
                'commodity' => 'KP-079', 'kabupaten' => 'KAB SUMEDANG', 'kecamatan' => 'Wado',
                'desa' => 'Cimungkal', 'latitude' => -7.0029856, 'longitude' => 108.1337070,
                'kode_kabupaten' => '3211', 'kode_kecamatan' => '321101', 'kode_desa' => '3211012001',
            ],
            'kakao' => [
                'external_id' => '9901', 'code' => 'DEMO-KAKAO-001', 'name' => 'KAKAO MAJU DEMO',
                'commodity' => 'KP-017', 'kabupaten' => 'KAB BANDUNG', 'kecamatan' => 'Pangalengan',
                'desa' => 'Margamulya', 'latitude' => -7.0250000, 'longitude' => 107.5190000,
                'kode_kabupaten' => '3204', 'kode_kecamatan' => '320401', 'kode_desa' => '3204012001',
            ],
            'cengkeh' => [
                'external_id' => '9902', 'code' => 'DEMO-CENGKEH-001', 'name' => 'CENGKEH SEJAHTERA DEMO',
                'commodity' => 'KP-016', 'kabupaten' => 'KAB CIANJUR', 'kecamatan' => 'Pacet',
                'desa' => 'Cipendawa', 'latitude' => -6.8200000, 'longitude' => 107.1400000,
                'kode_kabupaten' => '3203', 'kode_kecamatan' => '320301', 'kode_desa' => '3203012001',
            ],
            'kelapa' => [
                'external_id' => '9903', 'code' => 'DEMO-KELAPA-001', 'name' => 'KELAPA LESTARI DEMO',
                'commodity' => 'KP-056', 'kabupaten' => 'KAB BOGOR', 'kecamatan' => 'Cibinong',
                'desa' => 'Nanggewer', 'latitude' => -6.5800000, 'longitude' => 106.8000000,
                'kode_kabupaten' => '3201', 'kode_kecamatan' => '320101', 'kode_desa' => '3201012001',
            ],
        ];

        $groups = [];
        foreach ($definitions as $key => $definition) {
            $commodityId = $commodities[$definition['commodity']]
                ?? throw new RuntimeException('Komoditas demo tidak ditemukan: '.$definition['commodity']);

            $groups[$key] = RefKelompokTani::updateOrCreate(
                ['source' => RefKelompokTani::SOURCE_DISBUN, 'disbun_record_id' => $definition['external_id']],
                [
                    'kode' => $definition['code'], 'kode_kelompok' => $definition['code'],
                    'nama' => $definition['name'], 'ketua' => 'Profil Demo',
                    'jenis_komoditi' => (string) RefKomoditas::find($commodityId)?->nama,
                    'external_commodity_id' => null,
                    'external_commodity_code' => $definition['commodity'],
                    'external_commodity_name' => (string) RefKomoditas::find($commodityId)?->nama,
                    'commodity_ref_id' => $commodityId, 'commodity_mapping_status' => 'mapped',
                    'kabupaten' => $definition['kabupaten'], 'kecamatan' => $definition['kecamatan'],
                    'desa' => $definition['desa'], 'kelurahan' => $definition['desa'],
                    'kode_kabupaten' => $definition['kode_kabupaten'],
                    'kode_kecamatan' => $definition['kode_kecamatan'], 'kode_desa' => $definition['kode_desa'],
                    'latitude' => $definition['latitude'], 'longitude' => $definition['longitude'],
                    'status' => 'aktif', 'source_is_active' => true, 'is_verified' => true,
                    'sync_status' => RefKelompokTani::SYNC_SYNCED, 'quarantine_reason' => null,
                    'deleted_at' => null, 'last_synced_at' => now(),
                ],
            );
        }

        return $groups;
    }

    private function seedKnowledgeImages(): void
    {
        $diseaseAssets = [
            'PNY-UAT-001' => 'knowledge/penyakit/karat-daun-kopi.svg',
            'PNY-UAT-002' => 'knowledge/penyakit/busuk-buah-kakao.svg',
            'PNY-UAT-003' => 'knowledge/penyakit/bpkc-cengkeh.svg',
            'PNY-UAT-004' => 'knowledge/penyakit/busuk-pucuk-kelapa.svg',
        ];
        $symptomAssets = [
            'G-UAT-001' => 'knowledge/gejala/karat-daun-kopi.svg',
            'G-UAT-007' => 'knowledge/gejala/busuk-buah-kakao.svg',
            'G-UAT-013' => 'knowledge/gejala/bpkc-cengkeh.svg',
            'G-UAT-019' => 'knowledge/gejala/busuk-pucuk-kelapa.svg',
        ];

        foreach ($diseaseAssets as $code => $runtimePath) {
            $this->copyAsset($runtimePath);
            Penyakit::query()->where('kode', $code)->update(['image_path' => $runtimePath]);
        }
        foreach ($symptomAssets as $code => $runtimePath) {
            $this->copyAsset($runtimePath);
            Gejala::query()->where('kode', $code)->update(['image_path' => $runtimePath]);
        }
    }

    private function copyAsset(string $runtimePath): void
    {
        $source = database_path('seeders/assets/'.$runtimePath);
        if (! is_file($source)) {
            throw new RuntimeException('Asset demo tidak ditemukan: '.$source);
        }

        Storage::disk('public')->put($runtimePath, file_get_contents($source));
    }

    /** @param array<string, User> $accounts
     *  @param array<string, int> $commodities
     *  @param array<string, RefKelompokTani> $groups
     */
    private function seedStudyCases(array $accounts, array $commodities, array $groups): void
    {
        $cases = [
            [
                'key' => 'accepted', 'diagnosis_code' => 'DG-20260101-9001', 'request_code' => 'PM-20260101-9001', 'case_code' => 'KS-20260101-9001',
                'disease' => 'PNY-UAT-001', 'symptom' => 'G-UAT-001', 'commodity' => 'KP-079', 'group' => 'arosta',
                'request_status' => 'diterima', 'case_status' => 'diterima', 'assigned' => false,
                'lat' => -7.0250000, 'lng' => 107.5190000,
            ],
            [
                'key' => 'assigned', 'diagnosis_code' => 'DG-20260101-9002', 'request_code' => 'PM-20260101-9002', 'case_code' => 'KS-20260101-9002',
                'disease' => 'PNY-UAT-002', 'symptom' => 'G-UAT-007', 'commodity' => 'KP-017', 'group' => 'kakao',
                'request_status' => 'diterima', 'case_status' => 'ditugaskan', 'assigned' => true,
                'lat' => -7.0150000, 'lng' => 107.5050000,
            ],
            [
                'key' => 'in-progress', 'diagnosis_code' => 'DG-20260101-9003', 'request_code' => 'PM-20260101-9003', 'case_code' => 'KS-20260101-9003',
                'disease' => 'PNY-UAT-003', 'symptom' => 'G-UAT-013', 'commodity' => 'KP-016', 'group' => 'cengkeh',
                'request_status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'assigned' => true,
                'lat' => -6.8350000, 'lng' => 107.1550000,
            ],
            [
                'key' => 'completed', 'diagnosis_code' => 'DG-20260101-9004', 'request_code' => 'PM-20260101-9004', 'case_code' => 'KS-20260101-9004',
                'disease' => 'PNY-UAT-004', 'symptom' => 'G-UAT-019', 'commodity' => 'KP-056', 'group' => 'kelapa',
                'request_status' => 'diterima', 'case_status' => 'selesai', 'assigned' => true,
                'lat' => -6.5950000, 'lng' => 106.8150000,
            ],
            [
                'key' => 'rejected', 'diagnosis_code' => 'DG-20260101-9005', 'request_code' => 'PM-20260101-9005', 'case_code' => null,
                'disease' => 'PNY-UAT-001', 'symptom' => 'G-UAT-002', 'commodity' => 'KP-079', 'group' => 'arosta',
                'request_status' => 'ditolak', 'case_status' => null, 'assigned' => false,
                'lat' => -7.0120000, 'lng' => 108.1200000,
            ],
            [
                'key' => 'deferred', 'diagnosis_code' => 'DG-20260101-9006', 'request_code' => 'PM-20260101-9006', 'case_code' => 'KS-20260101-9006',
                'disease' => 'PNY-UAT-001', 'symptom' => 'G-UAT-003', 'commodity' => 'KP-079', 'group' => 'arosta',
                'request_status' => 'diterima', 'case_status' => 'ditunda', 'assigned' => true,
                'lat' => -7.0400000, 'lng' => 108.1500000,
            ],
        ];

        foreach ($cases as $definition) {
            $this->seedCase($definition, $accounts, $commodities, $groups);
        }
    }

    /** @param array<string, mixed> $definition
     *  @param array<string, User> $accounts
     *  @param array<string, int> $commodities
     *  @param array<string, RefKelompokTani> $groups
     */
    private function seedCase(array $definition, array $accounts, array $commodities, array $groups): void
    {
        $disease = Penyakit::query()->where('kode', $definition['disease'])->firstOrFail();
        $symptom = Gejala::query()->where('kode', $definition['symptom'])->firstOrFail();
        $commodityId = $commodities[$definition['commodity']] ?? throw new RuntimeException('Komoditas kasus demo tidak ditemukan.');
        $group = $groups[$definition['group']] ?? throw new RuntimeException('Poktan kasus demo tidak ditemukan.');

        $diagnosis = Diagnosis::updateOrCreate(
            ['kode' => $definition['diagnosis_code']],
            ['user_id' => $accounts['poktan']->id, 'commodity_id' => $commodityId, 'status' => Diagnosis::STATUS_SELESAI],
        );
        DiagnosisSymptom::updateOrCreate(
            ['diagnosis_id' => $diagnosis->id, 'symptom_id' => $symptom->id],
            ['symptom_name_snapshot' => $symptom->nama, 'cf_user' => 1.0],
        );
        $solution = $disease->solusi()->where('status', 'aktif')->first();
        DiagnosisResult::updateOrCreate(
            ['diagnosis_id' => $diagnosis->id, 'disease_id' => $disease->id],
            [
                'disease_name_snapshot' => $disease->nama, 'cf_value' => 0.90, 'ranking' => 1,
                'solution_snapshot' => $solution === null ? null : [['judul' => $solution->judul, 'deskripsi' => $solution->deskripsi]],
                'trace_snapshot' => [['gejala_id' => $symptom->id, 'gejala_nama' => $symptom->nama, 'cf_user' => 1.0, 'cf_pakar' => (float) AturanCf::where('penyakit_id', $disease->id)->where('gejala_id', $symptom->id)->value('cf_pakar'), 'cf_gejala' => 0.90]],
            ],
        );

        $permohonan = PermohonanPenanganan::updateOrCreate(
            ['permohonan_code' => $definition['request_code']],
            [
                'diagnosis_id' => $diagnosis->id, 'kelompok_tani_id' => $group->id,
                'kelompok_tani_name_snapshot' => $group->nama,
                'latitude_kasus' => $definition['lat'], 'longitude_kasus' => $definition['lng'],
                'alamat_kasus' => self::CASE_MARKER.' '.$group->kabupaten,
                'kode_kabupaten' => $group->kode_kabupaten, 'kabupaten' => $group->kabupaten,
                'kode_kecamatan' => $group->kode_kecamatan, 'kecamatan' => $group->kecamatan,
                'kode_desa' => $group->kode_desa, 'kelurahan' => $group->kelurahan,
                'catatan_pemohon' => self::CASE_MARKER.' Skenario: '.$definition['key'].'.',
                'status' => $definition['request_status'], 'created_by' => $accounts['poktan']->id,
                'reviewed_by' => $accounts['operator_uptd']->id, 'reviewed_at' => now(),
            ],
        );

        KeputusanPermohonan::updateOrCreate(
            ['permohonan_id' => $permohonan->id],
            [
                'keputusan' => $definition['request_status'] === 'diterima' ? 'diterima' : 'ditolak',
                'catatan' => self::CASE_MARKER.' Keputusan demo Operator UPTD.',
                'operator_id' => $accounts['operator_uptd']->id, 'decided_at' => now(),
            ],
        );

        if ($definition['case_code'] === null) {
            return;
        }

        $case = KasusPenanganan::withTrashed()->where('kasus_code', $definition['case_code'])->first();
        if ($case === null) {
            $case = new KasusPenanganan(['kasus_code' => $definition['case_code']]);
        }
        $case->fill([
            'permohonan_id' => $permohonan->id, 'current_status' => $definition['case_status'],
            'komoditas_id' => $commodityId, 'komoditas_code_snapshot' => $definition['commodity'],
            'komoditas_name_snapshot' => RefKomoditas::find($commodityId)?->nama,
            'penyakit_id' => $disease->id, 'penyakit_kode_snapshot' => $disease->kode,
            'penyakit_name_snapshot' => $disease->nama,
            'latitude_kasus' => $definition['lat'], 'longitude_kasus' => $definition['lng'],
            'created_by' => $accounts['operator_uptd']->id,
        ]);
        $case->save();
        if ($case->trashed()) {
            $case->restore();
        }

        $states = ['diterima'];
        if ($definition['assigned']) {
            $states[] = 'ditugaskan';
            if (in_array($definition['case_status'], ['sedang_direview', 'siap_dieksekusi', 'dalam_pelaksanaan', 'selesai', 'ditunda'], true)) {
                $states[] = 'sedang_direview';
            }
            if (in_array($definition['case_status'], ['siap_dieksekusi', 'dalam_pelaksanaan', 'selesai'], true)) {
                $states[] = 'siap_dieksekusi';
            }
            if (in_array($definition['case_status'], ['dalam_pelaksanaan', 'selesai'], true)) {
                $states[] = 'dalam_pelaksanaan';
            }
            if ($definition['case_status'] === 'selesai') {
                $states[] = 'selesai';
            }
            if ($definition['case_status'] === 'ditunda') {
                $states[] = 'ditunda';
            }
        }

        $notes = [
            'diterima' => 'Kasus demo lahir dari permohonan yang diterima.',
            'ditugaskan' => 'POPT demo ditugaskan oleh Operator UPTD.',
            'sedang_direview' => 'Gejala dan data lokasi telah diverifikasi.',
            'siap_dieksekusi' => 'Penanganan lapangan telah dipersiapkan.',
            'dalam_pelaksanaan' => 'Pengendalian lapangan sedang dilaksanakan.',
            'ditunda' => 'Penanganan demo ditunda untuk simulasi dashboard.',
            'selesai' => 'Penanganan lapangan selesai dan hasil telah dicatat.',
        ];
        foreach ($states as $index => $status) {
            RiwayatStatusPenanganan::firstOrCreate(
                ['kasus_id' => $case->id, 'status' => $status],
                ['previous_status' => $index === 0 ? null : $states[$index - 1], 'catatan' => self::CASE_MARKER.' '.$notes[$status], 'actor_id' => $status === 'diterima' ? $accounts['operator_uptd']->id : $accounts['popt']->id, 'created_at' => now()->subMinutes(count($states) - $index)],
            );
        }

        if ($definition['assigned']) {
            PenugasanPopt::updateOrCreate(
                ['kasus_id' => $case->id, 'popt_id' => $accounts['popt']->id],
                ['assigned_by' => $accounts['operator_uptd']->id, 'status' => $definition['case_status'] === 'selesai' ? 'selesai' : 'aktif', 'catatan' => self::CASE_MARKER, 'assigned_at' => now()],
            );
        }
    }
}
