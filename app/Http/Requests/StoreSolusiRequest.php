<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & POPT (Knowledge Manager) yang boleh mengelola
        return $this->user() !== null && $this->user()->hasRole(['admin', 'popt']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['required', 'integer', 'exists:penyakit,id'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'penyakit_id.required' => 'Solusi harus dikaitkan ke satu penyakit.',
            'penyakit_id.exists' => 'Penyakit yang dipilih tidak ditemukan.',
            'judul.required' => 'Judul solusi wajib diisi.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
        ];
    }
}
