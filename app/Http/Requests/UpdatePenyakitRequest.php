<?php

namespace App\Http\Requests;

use App\Contracts\KomoditasReferensiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePenyakitRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & Pakar (Knowledge Manager) yang boleh mengelola
        // basis pengetahuan — sesuai RBAC Matrix PRD §24
        // ("Penyakit & gejala": C/R/U/D untuk Knowledge Manager, admin
        // untuk Admin).
        return $this->user()?->hasRole(['admin', 'operator_uptd', 'popt']) ?? false;
    }

    public function rules(): array
    {
        // route model binding diasumsikan bernama {penyakit}
        // (Route::apiResource('penyakit', PenyakitController::class))
        $penyakitId = $this->route('penyakit')?->id ?? $this->route('penyakit');

        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('penyakit', 'kode')->ignore($penyakitId)],
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'komoditas_id' => ['sometimes', 'array'],
            'komoditas_id.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode penyakit sudah dipakai, gunakan kode lain.',
        ];
    }

    /**
     * Sama seperti StorePenyakitRequest — cek keberadaan komoditas_id
     * lewat KomoditasReferensiClient (tahap #8).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $komoditasIds = $this->input('komoditas_id', []);

            if (empty($komoditasIds)) {
                return;
            }

            $client = app(KomoditasReferensiClient::class);

            foreach ($komoditasIds as $id) {
                $komoditas = $client->find((int) $id);

                if ($komoditas === null) {
                    $validator->errors()->add(
                        'komoditas_id',
                        "Komoditas dengan id {$id} tidak ditemukan atau tidak aktif di referensi Disbun."
                    );
                }
            }
        });
    }
}
