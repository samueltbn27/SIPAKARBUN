<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['poktan', 'operator_uptd', 'popt'])],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Pilih peran Anda.',
            'role.in' => 'Peran yang dipilih tidak tersedia untuk pendaftaran mandiri.',
        ];
    }
}
