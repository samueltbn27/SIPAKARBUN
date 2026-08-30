<?php

namespace Database\Seeders;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\RefKomoditas;
use App\Models\Solusi;
use Illuminate\Database\Seeder;

/**
 * UAT / DEVELOPMENT DATA.
 * SIMULATED UAT CF — NOT EXPERT VALIDATED.
 *
 * This dataset intentionally has no image dependency. Licensed local assets
 * can be uploaded later through Knowledge CRUD and remain nullable today.
 */
class KnowledgeUatSeeder extends Seeder
{
    public function run(): void
    {
        $commodityIds = [
            'kopi' => $this->commodityId('KP-079', 'Kopi'),
            'kakao' => $this->commodityId('KP-017', 'Kakao'),
            'cengkeh' => $this->commodityId('KP-016', 'Cengkeh'),
            'kelapa' => $this->commodityId('KP-056', 'Kelapa'),
        ];

        $diseases = [
            'PNY-UAT-001' => [
                'name' => 'Karat Daun Kopi',
                'cause' => 'Hemileia vastatrix',
                'description' => 'Penyakit daun kopi yang ditandai bercak kuning hingga jingga, terutama pada permukaan bawah daun. Pada bercak dapat terbentuk massa spora seperti tepung berwarna jingga. Serangan berat dapat menyebabkan daun gugur dan melemahkan tanaman.',
                'commodities' => [$commodityIds['kopi'], RefKomoditas::where('kode', 'KP-080')->value('id')],
                'solution_title' => 'Pengendalian Terpadu Karat Daun Kopi',
                'solution' => 'Lakukan sanitasi dan pemeliharaan kebun, pengaturan naungan dan pemangkasan sesuai kondisi kebun, serta pemupukan berimbang. Gunakan bahan tanaman yang tahan atau toleran jika tersedia. Penggunaan fungisida hanya dilakukan apabila diperlukan sesuai rekomendasi teknis dan ketentuan penggunaan pestisida. Untuk serangan berat, ajukan bantuan penanganan kepada petugas.',
                'symptoms' => [
                    ['G-UAT-001', 'Muncul bercak kuning muda pada permukaan bawah daun', 0.70],
                    ['G-UAT-002', 'Bercak berkembang menjadi kuning tua atau jingga', 0.75],
                    ['G-UAT-003', 'Pada bercak terlihat serbuk seperti tepung berwarna jingga', 0.95],
                    ['G-UAT-004', 'Bercak yang berdekatan membesar dan menyatu', 0.60],
                    ['G-UAT-005', 'Daun terserang gugur sebelum waktunya', 0.75],
                    ['G-UAT-006', 'Pada serangan berat ranting atau cabang mengalami kematian', 0.55],
                ],
            ],
            'PNY-UAT-002' => [
                'name' => 'Busuk Buah Kakao',
                'cause' => 'Phytophthora palmivora',
                'description' => 'Penyakit pada buah kakao yang menyebabkan bercak coklat kehitaman yang dapat berkembang dengan cepat hingga buah menjadi busuk. Pada kondisi lembap dapat terlihat pertumbuhan berwarna putih pada permukaan buah.',
                'commodities' => [$commodityIds['kakao']],
                'solution_title' => 'Sanitasi dan Pengendalian Busuk Buah Kakao',
                'solution' => 'Petik dan tangani buah yang terserang agar tidak menjadi sumber penularan. Lakukan sanitasi kebun, pemangkasan tanaman dan pohon pelindung untuk mengurangi kelembapan, serta perbaiki drainase. Gunakan bahan tanaman tahan atau toleran jika tersedia. Kasus berat sebaiknya ditangani bersama petugas teknis.',
                'symptoms' => [
                    ['G-UAT-007', 'Terdapat bercak coklat kehitaman pada buah', 0.80],
                    ['G-UAT-008', 'Bercak bermula dari pangkal atau ujung buah', 0.65],
                    ['G-UAT-009', 'Bercak meluas hingga buah menjadi busuk berwarna hitam', 0.90],
                    ['G-UAT-010', 'Pada kondisi lembap terdapat pertumbuhan putih pada permukaan buah', 0.95],
                    ['G-UAT-011', 'Buah terserang terasa lembek atau basah', 0.70],
                    ['G-UAT-012', 'Serangan pada buah muda menyebabkan perkembangan buah/biji terganggu', 0.65],
                ],
            ],
            'PNY-UAT-003' => [
                'name' => 'Bakteri Pembuluh Kayu Cengkeh (BPKC)',
                'cause' => 'Ralstonia syzygii',
                'description' => 'Penyakit serius pada tanaman cengkeh yang dapat menyebabkan gugur daun mendadak, kematian ranting dari bagian pucuk, kemudian berkembang hingga tanaman mengalami kematian.',
                'commodities' => [$commodityIds['cengkeh']],
                'solution_title' => 'Pengendalian BPKC Cengkeh',
                'solution' => 'Lakukan sanitasi kebun dan penanganan tanaman atau bagian tanaman yang menunjukkan serangan sesuai tingkat kerusakan. Pengendalian vektor dan tindakan eradikasi harus mengikuti rekomendasi teknis. Kasus yang dicurigai BPKC sebaiknya dikonsultasikan kepada POPT.',
                'symptoms' => [
                    ['G-UAT-013', 'Daun berguguran secara mendadak', 0.90],
                    ['G-UAT-014', 'Ranting dekat pucuk mulai mati', 0.80],
                    ['G-UAT-015', 'Kematian ranting berkembang dari pucuk ke bagian bawah', 0.90],
                    ['G-UAT-016', 'Cabang atau tanaman mengalami layu mendadak', 0.75],
                    ['G-UAT-017', 'Daun banyak gugur dan ranting menjadi kering', 0.80],
                    ['G-UAT-018', 'Pada serangan berat tanaman akhirnya mati', 0.60],
                ],
            ],
            'PNY-UAT-004' => [
                'name' => 'Busuk Pucuk Kelapa',
                'cause' => 'Phytophthora palmivora',
                'description' => 'Penyakit yang menyerang pucuk atau titik tumbuh kelapa. Gejala dapat diawali dengan perubahan warna janur, rebahnya daun tombak, kemudian pembusukan pada bagian pucuk.',
                'commodities' => [$commodityIds['kelapa']],
                'solution_title' => 'Pengendalian Busuk Pucuk Kelapa',
                'solution' => 'Lakukan sanitasi dan pemeriksaan bagian pucuk tanaman. Tanaman dengan serangan berat perlu segera ditangani agar tidak menjadi sumber inokulum. Penanganan lebih lanjut dan tindakan eradikasi harus mengikuti rekomendasi teknis POPT.',
                'symptoms' => [
                    ['G-UAT-019', 'Janur atau daun tombak memucat', 0.75],
                    ['G-UAT-020', 'Janur menjadi condong kemudian rebah atau patah', 0.90],
                    ['G-UAT-021', 'Daun bagian bawah berubah kuning suram', 0.55],
                    ['G-UAT-022', 'Daun kemudian berubah coklat dan dapat rontok', 0.60],
                    ['G-UAT-023', 'Umbut atau titik tumbuh mengalami pembusukan', 0.95],
                    ['G-UAT-024', 'Bagian umbut yang membusuk mengeluarkan bau tidak sedap', 0.85],
                ],
            ],
        ];

        foreach ($diseases as $code => $definition) {
            $disease = Penyakit::updateOrCreate(
                ['kode' => $code],
                [
                    'nama' => $definition['name'],
                    'deskripsi' => $definition['description'].' Penyebab: '.$definition['cause'].'.',
                    'status' => Penyakit::STATUS_AKTIF,
                ],
            );

            foreach (array_unique(array_filter($definition['commodities'])) as $commodityId) {
                PenyakitKomoditas::updateOrCreate([
                    'penyakit_id' => $disease->id,
                    'komoditas_id' => $commodityId,
                ]);
            }

            $solution = Solusi::updateOrCreate(
                ['penyakit_id' => $disease->id, 'judul' => $definition['solution_title']],
                ['deskripsi' => $definition['solution'], 'status' => Solusi::STATUS_AKTIF],
            );

            foreach ($definition['symptoms'] as [$symptomCode, $symptomName, $cf]) {
                $symptom = Gejala::updateOrCreate(
                    ['kode' => $symptomCode],
                    ['nama' => $symptomName, 'deskripsi' => null, 'status' => Gejala::STATUS_AKTIF],
                );

                AturanCf::updateOrCreate(
                    ['penyakit_id' => $disease->id, 'gejala_id' => $symptom->id, 'version' => 1],
                    [
                        'cf_pakar' => $cf, // SIMULATED UAT CF — NOT EXPERT VALIDATED
                        'status' => AturanCf::STATUS_AKTIF,
                        'created_by' => null,
                        'updated_by' => null,
                    ],
                );
            }
        }
    }

    private function commodityId(string $code, string $name): int
    {
        return (int) (RefKomoditas::query()->where('kode', $code)->value('id')
            ?? RefKomoditas::query()->where('nama', 'like', $name.'%')->value('id')
            ?? throw new \RuntimeException("Komoditas internal {$name} belum tersedia."));
    }
}
