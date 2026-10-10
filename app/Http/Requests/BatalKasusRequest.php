<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BatalKasusRequest — pembatalan kasus dini oleh Admin/Operator UPTD.
 */
class BatalKasusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'operator_uptd']) === true;
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan minimal 10 karakter.',
        ];
    }
}
