<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerpanjanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('popt') === true;
    }

    public function rules(): array
    {
        return [
            'proposed_deadline_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposed_deadline_at.required' => 'Usulan target baru wajib diisi.',
            'proposed_deadline_at.date' => 'Usulan target baru harus berupa tanggal dan waktu yang valid.',
            'reason.required' => 'Alasan perpanjangan wajib diisi.',
        ];
    }
}
