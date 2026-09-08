<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user?->hasAnyRole(['admin', 'operator_uptd'])) {
            return true;
        }

        $solusi = $this->route('solusi');
        return $user?->hasRole('popt') === true
            && is_object($solusi)
            && $solusi->status === 'draft';
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
