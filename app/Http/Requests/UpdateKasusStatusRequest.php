<?php

namespace App\Http\Requests;

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
