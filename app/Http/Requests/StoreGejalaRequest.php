<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & POPT (Knowledge Manager) yang boleh mengelola
        return $this->user() !== null && $this->user()->hasRole(['admin', 'popt']);
    }

    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('gejala', 'kode')],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode gejala sudah dipakai, gunakan kode lain.',
            'nama.required' => 'Nama gejala wajib diisi.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
        ];
    }
}
