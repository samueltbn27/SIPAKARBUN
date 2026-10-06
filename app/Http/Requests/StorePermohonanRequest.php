<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StorePermohonanRequest — validasi input pembuatan permohonan penanganan.
 *
 * Aturan kunci:
 *   - `latitude_kasus`/`longitude_kasus` adalah koordinat KASUS/serangan
 *     (kontrak §10), rentang diverifikasi di sini. Form lat/long KASUS
 *     wajib diisi oleh pemohon dan rentangnya diverifikasi di sini. Lokasi
 *     kasus tidak diturunkan dari koordinat referensi kelompok tani.
 *   - `lokasi_dikonfirmasi` wajib dicentang (`accepted`): pernyataan
 *     pemohon bahwa titik lokasi kasus sudah benar.
 *   - `evidences` dibatasi: jumlah maksimal 5 file, ukuran tiap ≤ 5 MB,
 *     dan MIME whitelist (jpg/png/webp) — konsisten dengan
 *     EvidenceFileHandler.
 *   - `kelompok_tani_id` TIDAK dikirim oleh Poktan (`prohibited`) —
 *     identitas Poktan selalu diambil dari akun login di
 *     PermohonanService. Admin/Operator UPTD yang membuat atas nama
 *     Poktan tetap wajib mengirimnya (`required`).
 */
class StorePermohonanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // Poktan adalah pemohon utama; admin/operator boleh membuat atas
        // nama Poktan bila diminta (pengecualian layanan).
        return $user->hasAnyRole(['poktan', 'admin', 'operator_uptd']);
    }

    public function rules(): array
    {
        // Poktan tidak memilih kelompok tani di form — identitas diambil
        // dari akun login. Field ini dilarang dikirim agar tidak ada
        // ambiguitas/tampering antara pilihan form vs akun.
        $kelompokTaniRule = $this->user()?->hasRole('poktan')
            ? [Rule::prohibitedIf(true)]
            : ['required', 'integer'];

        return [
            'diagnosis_id' => ['required', 'integer', Rule::exists('diagnoses', 'id')],
            'kelompok_tani_id' => $kelompokTaniRule,
            'latitude_kasus' => ['required', 'numeric', 'between:-90,90'],
            'longitude_kasus' => ['required', 'numeric', 'between:-180,180'],
            'alamat_kasus' => ['nullable', 'string', 'max:500'],
            // Pemohon wajib mencentang bahwa titik lokasi kasus sudah benar.
            'lokasi_dikonfirmasi' => ['required', 'accepted'],
            'kode_kabupaten' => ['nullable', 'string', 'max:50'],
            'kabupaten' => ['nullable', 'string', 'max:150'],
            'kode_kecamatan' => ['nullable', 'string', 'max:50'],
            'kecamatan' => ['nullable', 'string', 'max:150'],
            'kode_desa' => ['nullable', 'string', 'max:50'],
            'kelurahan' => ['nullable', 'string', 'max:150'],
            'catatan_pemohon' => ['nullable', 'string', 'max:2000'],
            'evidences' => ['sometimes', 'array', 'max:5'],
            'evidences.*' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
