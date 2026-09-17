<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AssignPoptRequest — validasi penugasan POPT ke kasus (kontrak §13).
 *
 * Kevalidan POPT (role popt + is_active) sengaja TIDAK di request rule
 * `exists` saja; dicek menyeluruh di KasusService agar business logic
 * tidak bocor ke lapisan HTTP.
 */
class AssignPoptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['operator_uptd', 'admin']) === true;
    }

    public function rules(): array
    {
        return [
            'popt_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'deadline_at' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        $deadline = Carbon::parse($value);
                    } catch (\Throwable) {
                        return;
                    }

                    if (! $deadline->isAfter(now())) {
                        $fail('Target penyelesaian harus berada di masa depan.');
                    }
                },
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'deadline_at.required' => 'Target penyelesaian wajib diisi saat menugaskan POPT.',
            'deadline_at.date' => 'Target penyelesaian harus berupa tanggal dan waktu yang valid.',
        ];
    }
}
