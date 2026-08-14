<?php

namespace App\Http\Requests;

use App\Contracts\KomoditasReferensiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePenyakitRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & Pakar (Knowledge Manager) yang boleh mengelola
        // basis pengetahuan — sesuai RBAC Matrix PRD §24
        // ("Penyakit & gejala": C/R/U/D untuk Knowledge Manager, admin
        // untuk Admin).
        return $this->user()?->hasRole(['admin', 'pakar']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('penyakit', 'kode')],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],

            // Opsional: assign komoditas terkait sekalian saat bikin penyakit.
            // komoditas_id di sini merujuk ke ref_komoditas.id (shared).
            // Format dasarnya (integer) dicek di sini; keberadaan
            // ASLI-nya (apakah id itu benar ada & aktif di referensi
            // Shared Integration) dicek di withValidator() di bawah,
            // lewat KomoditasReferensiClient — bukan query tabel lokal,
            // karena tabel itu bukan milik modul ini (tahap #8).
            'komoditas_id' => ['sometimes', 'array'],
            'komoditas_id.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode penyakit sudah dipakai, gunakan kode lain.',
            'nama.required' => 'Nama penyakit wajib diisi.',
        ];
    }

    /**
     * Validasi tambahan: pastikan tiap komoditas_id yang dikirim benar
     * ada & aktif menurut Shared Integration — bukan asal angka.
     * Ini memenuhi DoD "data komoditas invalid tidak masuk" (tahap #1).
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
