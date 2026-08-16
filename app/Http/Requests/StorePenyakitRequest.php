<?php

namespace App\Http\Requests;

use App\Models\RefKomoditas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePenyakitRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & POPT (Knowledge Manager) yang boleh mengelola
        // basis pengetahuan — sesuai RBAC Matrix PRD §24
        // ("Penyakit & gejala": C/R/U/D untuk Knowledge Manager, admin
        // untuk Admin).
        return $this->user()?->hasRole(['admin', 'popt']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('penyakit', 'kode')],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],

            // Opsional: assign komoditas terkait sekalian saat bikin penyakit.
            // komoditas_id di sini merujuk ke ref_komoditas.id (shared).
            // Format dasarnya (integer) dicek di sini; keberadaan ASLI-nya
            // dicek di withValidator() di bawah terhadap tabel ref_komoditas:
            // hanya yang terverifikasi & tidak dikarantina yang lolos
            // (M1-AC-006, INT-FR-007).
            'komoditas_id' => ['sometimes', 'array'],
            'komoditas_id.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode penyakit sudah dipakai, gunakan kode lain.',
            'nama.required' => 'Nama penyakit wajib diisi.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
        ];
    }

    /**
     * Validasi tambahan: pastikan tiap komoditas_id yang dikirim benar
     * ada & terverifikasi di ref_komoditas — bukan asal angka.
     * Ini memenuhi M1-AC-006 "data komoditas invalid tidak masuk".
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $komoditasIds = $this->input('komoditas_id', []);

            if (empty($komoditasIds)) {
                return;
            }

            $validIds = RefKomoditas::tersedia()
                ->whereIn('id', $komoditasIds)
                ->pluck('id')
                ->all();

            foreach ($komoditasIds as $id) {
                if (!in_array((int) $id, $validIds, true)) {
                    $validator->errors()->add(
                        'komoditas_id',
                        "Komoditas dengan id {$id} tidak ditemukan, belum terverifikasi, atau dikarantina."
                    );
                }
            }
        });
    }
}
