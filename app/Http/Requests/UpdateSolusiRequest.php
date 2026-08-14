<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role Pakar/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'pakar']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['sometimes', 'required', 'integer', 'exists:penyakit,id'],
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'penyakit_id.exists' => 'Penyakit yang dipilih tidak ditemukan.',
        ];
    }
}
