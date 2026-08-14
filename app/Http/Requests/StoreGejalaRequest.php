<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role Pakar/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'operator_uptd', 'popt']);
    }

    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('gejala', 'kode')],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode gejala sudah dipakai, gunakan kode lain.',
            'nama.required' => 'Nama gejala wajib diisi.',
        ];
    }
}
