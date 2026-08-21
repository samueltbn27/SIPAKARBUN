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
 *     memang WAJIB? Tidak — Oracle sengaja nullable karena operator boleh
 *     melengkapi koordinat di tahap kasus; tapi jika diberikan harus valid.
 *   - `evidences` dibatasi: jumlah maksimal 5 file, ukuran tiap ≤ 5 MB,
 *     dan MIME whitelist (jpg/png/webp) — konsisten dengan
 *     EvidenceFileHandler.
 *   - `kelompok_tani_id` hanya `integer`; kevalidan terhadap sumber
 *     kebenaran Shared Integration dikerjakan di PermohonanService.
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
        return [
            'diagnosis_id' => ['required', 'integer', Rule::exists('diagnoses', 'id')],
            'kelompok_tani_id' => ['required', 'integer'],
            'latitude_kasus' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_kasus' => ['nullable', 'numeric', 'between:-180,180'],
            'alamat_kasus' => ['nullable', 'string', 'max:500'],
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
