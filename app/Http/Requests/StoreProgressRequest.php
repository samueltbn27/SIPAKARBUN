<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('popt') === true;
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
            'catatan.required' => 'Catatan progress wajib diisi.',
        ];
    }
}
