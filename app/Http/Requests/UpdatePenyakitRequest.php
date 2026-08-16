<?php

namespace App\Http\Requests;

use App\Models\RefKomoditas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePenyakitRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & POPT (Knowledge Manager) yang boleh mengelola
        // basis pengetahuan — sesuai RBAC Matrix PRD §24.
        return $this->user()?->hasRole(['admin', 'popt']) ?? false;
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
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
            'komoditas_id' => ['sometimes', 'array'],
            'komoditas_id.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode penyakit sudah dipakai, gunakan kode lain.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
        ];
    }

    /**
     * Sama seperti StorePenyakitRequest — cek keberadaan komoditas_id
     * terhadap ref_komoditas yang terverifikasi & tidak dikarantina.
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
