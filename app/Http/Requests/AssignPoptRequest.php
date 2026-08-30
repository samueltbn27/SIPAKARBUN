<?php

namespace App\Http\Requests;

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
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
