<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role Pakar/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'pakar']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['required', 'integer', 'exists:penyakit,id'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
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
