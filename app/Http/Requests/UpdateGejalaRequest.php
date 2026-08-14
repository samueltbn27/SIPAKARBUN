<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role Pakar/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'pakar']);
    }

    public function rules(): array
    {
        $gejalaId = $this->route('gejala')?->id ?? $this->route('gejala');

        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('gejala', 'kode')->ignore($gejalaId)],
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode gejala sudah dipakai, gunakan kode lain.',
        ];
    }
}
