<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role POPT/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'popt']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['required', 'integer', 'exists:penyakit,id'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'aktif', 'nonaktif'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Data baru otomatis berstatus draft (belum dipublish) kecuali
        // pemanggil secara eksplisit mengirim status lain.
        if (! $this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
    }

    public function messages(): array
    {
        return [
            'penyakit_id.required' => 'Solusi harus dikaitkan ke satu penyakit.',
            'penyakit_id.exists' => 'Penyakit yang dipilih tidak ditemukan.',
            'judul.required' => 'Judul solusi wajib diisi.',
        ];
    }
}
