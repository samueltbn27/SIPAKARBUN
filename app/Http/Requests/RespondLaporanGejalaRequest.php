<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondLaporanGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('laporanGejala');

        return $this->user()?->hasRole('poktan') === true
            && $report !== null
            && (int) $report->created_by === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'additional_information' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
