<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TolakPermohonanRequest — aksi Operator UPTD menolak permohonan.
 * Alasan penolakan (catatan) WAJIB diisi (kontrak §12 "ditolak + alasan").
 */
class TolakPermohonanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('operator_uptd') === true;
    }

    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
