<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user?->hasAnyRole(['admin', 'operator_uptd'])) {
            return true;
        }

        $gejala = $this->route('gejala');
        return $user?->hasRole('popt') === true
            && is_object($gejala)
            && $gejala->status === 'draft';
    }

    public function rules(): array
    {
        $gejalaId = $this->route('gejala')?->id ?? $this->route('gejala');

        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('gejala', 'kode')->ignore($gejalaId)],
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode gejala sudah dipakai, gunakan kode lain.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
        ];
    }
}
