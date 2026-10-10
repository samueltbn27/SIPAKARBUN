<?php

namespace App\Http\Requests;

use App\Models\LaporanGejala;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewLaporanOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'operator_uptd']) ?? false;
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in([LaporanGejala::REVIEW_SETUJU, LaporanGejala::REVIEW_DITOLAK])],
            'catatan' => ['required_if:keputusan,'.LaporanGejala::REVIEW_DITOLAK, 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'keputusan.required' => 'Pilih keputusan review (setuju atau tolak).',
            'keputusan.in' => 'Keputusan review tidak valid.',
            'catatan.required_if' => 'Tuliskan alasan penolakan agar POPT dapat menindaklanjuti.',
        ];
    }
}
