<?php

namespace App\Http\Requests;

/**
 * Approval includes an active POPT and a future target in the same operation.
 */
class TerimaPermohonanRequest extends AssignPoptRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('operator_uptd') === true;
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'popt_id.required' => 'Pilih POPT yang bertanggung jawab sebelum menyetujui permohonan.',
            'popt_id.exists' => 'POPT yang dipilih tidak ditemukan.',
            'deadline_at.required' => 'Target penyelesaian wajib diisi sebelum menyetujui permohonan.',
        ]);
    }
}
