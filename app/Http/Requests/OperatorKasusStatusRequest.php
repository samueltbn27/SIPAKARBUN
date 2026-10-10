<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * OperatorKasusStatusRequest — intervensi Admin/Operator UPTD atas status
 * kasus kerja. State machine yang dipakai sama dengan POPT
 * (config/kasus.php); otorisasi dibatasi admin|operator_uptd.
 */
class OperatorKasusStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'operator_uptd']) === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(config('kasus.statuses', []))],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status kasus tidak dikenali.',
        ];
    }
}
