<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /** @var array<string, string> */
    public const ROLE_OPTIONS = [
        'operator_uptd' => 'Operator UPTD',
        'popt' => 'POPT',
        'poktan' => 'Poktan / Gapoktan',
        'pimpinan' => 'Pimpinan',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => ['required', 'string', 'in:'.implode(',', array_keys(self::ROLE_OPTIONS))],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/',
            ],
            'agree_terms' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama lengkap minimal 3 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar. Silakan gunakan email lain atau login.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung huruf.',
            'password.numbers' => 'Password harus mengandung angka.',
            'password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
            'password.symbols' => 'Password harus mengandung simbol.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'phone.required' => 'Nomor HP/WhatsApp wajib diisi.',
            'phone.regex' => 'Format nomor HP tidak valid. Contoh: 081234567890 atau +6281234567890.',
            'agree_terms.required' => 'Anda harus menyetujui Syarat & Ketentuan.',
            'agree_terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan.',
        ];
    }
}
