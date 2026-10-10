<?php

namespace App\Http\Requests;

use App\Models\KasusPenanganan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateKasusStatusRequest — aksi POPT mengubah status kasus (kontrak §16).
 *
 * Daftar status diset dari config/kasus.php (single source of truth state
 * machine), superset yang lebih luas dilarang validasi.
 */
class UpdateKasusStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('popt') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(config('kasus.statuses', [])), Rule::notIn([KasusPenanganan::STATUS_SELESAI])],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status kasus tidak dikenali.',
            'status.not_in' => 'Penyelesaian kasus wajib melalui Laporan Hasil Penanganan.',
        ];
    }
}
