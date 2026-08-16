<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & POPT (Knowledge Manager) yang boleh mengelola
        return $this->user() !== null && $this->user()->hasRole(['admin', 'popt']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['sometimes', 'required', 'integer', 'exists:penyakit,id'],
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'penyakit_id.exists' => 'Penyakit yang dipilih tidak ditemukan.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
        ];
    }
}
