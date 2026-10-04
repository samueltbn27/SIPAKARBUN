<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewLaporanGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('popt') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:perlu_informasi,duplikat,buat_draft'],
            'review_note' => ['required_if:action,perlu_informasi,duplikat', 'nullable', 'string', 'max:2000'],
            'draft_symptom_name' => ['required_if:action,buat_draft', 'nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_note.required_if' => 'Tuliskan catatan untuk Poktan agar tindak lanjut jelas.',
            'draft_symptom_name.required_if' => 'Isi nama gejala sebelum membuat draft.',
        ];
    }
}
