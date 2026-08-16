<?php

namespace Database\Seeders;

use App\Models\Penyakit;
use App\Models\Solusi;
use Illuminate\Database\Seeder;

/**
 * Data contoh rekomendasi solusi/penanganan per penyakit.
 */
class SolusiSeeder extends Seeder
{
    public function run(): void
    {
        // [kode_penyakit, judul, deskripsi]
        $data = [
            ['PY-001', 'Pangkas & musnahkan daun terinfeksi', 'Pangkas daun yang menunjukkan gejala, musnahkan (bakar/kubur), lalu aplikasikan fungisida berbahan aktif triazol sesuai dosis anjuran.'],
            ['PY-001', 'Tanam varietas tahan karat daun', 'Ganti/sulam dengan varietas kopi yang punya ketahanan terhadap karat daun, misalnya varietas kopi robusta unggul lokal.'],
            ['PY-002', 'Sanitasi & fungisida tembaga', 'Panen dan buang buah yang terinfeksi secara rutin, semprot fungisida berbahan dasar tembaga (Cu) pada tanaman sekitar.'],
            ['PY-003', 'Bongkar tanaman terinfeksi', 'Bongkar dan musnahkan tanaman yang terinfeksi berat berikut akarnya, aplikasikan fungisida akar pada tanaman di sekitarnya sebagai pencegahan.'],
            ['PY-004', 'Kurangi kelembaban & agen hayati', 'Perbaiki drainase di sekitar pangkal batang untuk mengurangi kelembaban berlebih, aplikasikan agen hayati Trichoderma sp.'],
            ['PY-005', 'Pangkas & fungisida mankozeb', 'Pangkas bagian tanaman yang terinfeksi, aplikasikan fungisida berbahan aktif mankozeb sesuai dosis anjuran.'],
            ['PY-006', 'Kerok kanker & olesi fungisida', 'Kerok bagian batang yang menunjukkan gejala kanker sampai jaringan sehat, olesi luka dengan fungisida pasta.'],
        ];

        foreach ($data as [$kodePenyakit, $judul, $deskripsi]) {
            $penyakit = Penyakit::where('kode', $kodePenyakit)->first();

            if (! $penyakit) {
                continue;
            }

            Solusi::updateOrCreate(
                ['penyakit_id' => $penyakit->id, 'judul' => $judul],
                ['deskripsi' => $deskripsi, 'is_active' => true]
            );
        }
    }
}
