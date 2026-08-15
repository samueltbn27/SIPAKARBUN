<?php

namespace App\Http\Requests;

use App\Contracts\KnowledgeApiClient;
use App\Contracts\KomoditasReferensiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi input saat user mengirim transaksi diagnosis baru.
 *
 * Struktur payload yang diterima:
 *   commodity_id : int — komoditas yang dipilih (ref_komoditas.id shared)
 *   symptom_ids  : array<int> — daftar gejala yang dipilih user
 *
 * Validasi "dasar" di sini (tahap #3):
 *   - format & keunikan id gejala,
 *   - komoditas benar ada & aktif menurut Shared Integration,
 *   - gejala yang dikirim benar ada di Knowledge API Mahasiswa 1.
 *
 * Mesin hitung CF (forward chaining / kombinasi certainty factor) bukan
 * bagian request ini — itu dilakukan service diagnosis pada tahap berikutnya.
 */
class StoreDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Diagnosis dilakukan oleh user yang sudah login. Role spesifik
        // (petani/penyuluh) belum didefinisikan ketat di PRD M2.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'commodity_id' => ['required', 'integer', 'min:1'],
            'symptom_ids' => ['required', 'array', 'min:1', 'max:50'],
            'symptom_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'commodity_id.required' => 'Komoditas wajib dipilih.',
            'symptom_ids.required' => 'Minimal satu gejala wajib dipilih.',
            'symptom_ids.*.distinct' => 'Gejala tidak boleh dipilih lebih dari sekali.',
        ];
    }

    /**
     * Validasi tambahan lewat client eksternal (bukan query tabel lokal):
     *   - komoditas_id: ada & aktif di referensi Shared Integration.
     *   - tiap symptom_id: ada di Knowledge API Mahasiswa 1.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $komoditasId = $this->input('commodity_id');

            if ($komoditasId !== null) {
                $komoditas = app(KomoditasReferensiClient::class)->find((int) $komoditasId);

                if ($komoditas === null) {
                    $validator->errors()->add(
                        'commodity_id',
                        "Komoditas dengan id {$komoditasId} tidak ditemukan atau tidak aktif di referensi."
                    );
                }
            }

            $symptomIds = $this->input('symptom_ids', []);

            if ($symptomIds !== []) {
                $knowledge = app(KnowledgeApiClient::class);
                $validIds = $knowledge->gejala()->pluck('id')->map(fn ($id): int => (int) $id)->all();

                foreach ($symptomIds as $id) {
                    if (! in_array((int) $id, $validIds, true)) {
                        $validator->errors()->add(
                            'symptom_ids',
                            "Gejala dengan id {$id} tidak ditemukan di basis pengetahuan."
                        );
                    }
                }
            }
        });
    }
}
