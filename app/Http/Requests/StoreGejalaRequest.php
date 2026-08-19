<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role POPT/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'popt']);
    }

    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('gejala', 'kode')],
            'nama' => ['required', 'string', 'max:150'],
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
            'kode.unique' => 'Kode gejala sudah dipakai, gunakan kode lain.',
            'nama.required' => 'Nama gejala wajib diisi.',
        ];
    }
}
