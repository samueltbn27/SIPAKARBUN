<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TerimaPermohonanRequest — aksi Operator UPTD menyetujui permohonan.
 * Catatan keputusan bersifat opsional.
 */
class TerimaPermohonanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('operator_uptd') === true;
    }

    public function rules(): array
    {
        return [
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
