<?php

namespace App\Http\Requests;

use App\Contracts\KnowledgeApiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Exceptions\KnowledgeApiException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Validasi input saat user mengirim transaksi diagnosis baru.
 *
 * Struktur payload yang diterima:
 *   commodity_id      : int — komoditas yang dipilih (ref_komoditas.id shared)
 *   symptom_ids       : array<int> — daftar gejala yang dipilih user
 *   symptom_confidence: array<gejala_id => float> OPSIONAL — tingkat
 *                       keyakinan user per gejala (0.0 s.d. 1.0).
 *                       Gejala tanpa nilai dianggap 1.0 ("yakin").
 *
 * Validasi "dasar" di sini (tahap #3):
 *   - format & keunikan id gejala,
 *   - rentang nilai symptom_confidence dan hanya untuk gejala terpilih,
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
            'symptom_confidence' => ['sometimes', 'array'],
            'symptom_confidence.*' => ['numeric', 'between:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'commodity_id.required' => 'Komoditas wajib dipilih.',
            'symptom_ids.required' => 'Minimal satu gejala wajib dipilih.',
            'symptom_ids.*.distinct' => 'Gejala tidak boleh dipilih lebih dari sekali.',
            'symptom_confidence.*.between' => 'Tingkat keyakinan harus antara 0 dan 1.',
        ];
    }

    /**
     * Validasi tambahan lewat client eksternal (bukan query tabel lokal):
     *   - komoditas_id: ada & aktif di referensi Shared Integration.
     *   - tiap symptom_id: ada di Knowledge API Mahasiswa 1.
     *
     * Semua panggilan eksternal dibungkus exception handling: kalau
     * Knowledge API / referensi sedang turun, user melihat pesan validasi
     * yang wajar (422), bukan error 500 mentah.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $komoditasId = $this->input('commodity_id');

            if ($komoditasId !== null) {
                try {
                    $komoditas = app(KomoditasReferensiClient::class)->find((int) $komoditasId);

                    if ($komoditas === null) {
                        $validator->errors()->add(
                            'commodity_id',
                            "Komoditas dengan id {$komoditasId} tidak ditemukan atau tidak aktif di referensi."
                        );
                    }
                } catch (KnowledgeApiException $e) {
                    Log::warning('Referensi komoditas gagal saat validasi diagnosis.', [
                        'message' => $e->getMessage(),
                    ]);

                    $validator->errors()->add(
                        'commodity_id',
                        'Layanan referensi komoditas sedang tidak tersedia. Silakan coba lagi.'
                    );
                }
            }

            $symptomIds = $this->input('symptom_ids', []);

            if ($symptomIds !== []) {
                try {
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
                } catch (KnowledgeApiException $e) {
                    Log::warning('Knowledge API gagal saat validasi diagnosis.', [
                        'message' => $e->getMessage(),
                    ]);

                    $validator->errors()->add(
                        'symptom_ids',
                        'Basis pengetahuan sedang tidak tersedia. Silakan coba lagi.'
                    );
                }

                // Tingkat keyakinan hanya boleh diberikan untuk gejala yang
                // memang dipilih (symptom_ids) — mencegah nilai tak berdasar.
                $confidence = $this->input('symptom_confidence', []);
                $selectedIds = array_map('intval', $symptomIds);

                if (is_array($confidence) && $confidence !== []) {
                    foreach (array_keys($confidence) as $key) {
                        if (! in_array((int) $key, $selectedIds, true)) {
                            $validator->errors()->add(
                                'symptom_confidence',
                                "Tingkat keyakinan untuk gejala {$key} tidak sesuai dengan symptom_ids yang dipilih."
                            );
                        }
                    }
                }
            }
        });
    }
}
