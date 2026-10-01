<?php

namespace App\Http\Requests;

use App\Models\RefKelompokTani;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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

    /** @var array<string, string> */
    public const PUBLIC_ROLE_OPTIONS = [
        'poktan' => 'Poktan / Gapoktan',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Registrasi publik tidak mengirim role — selalu Poktan.
        if (! $this->filled('role')) {
            $this->merge(['role' => 'poktan']);
        }
    }

    public function rules(): array
    {
        $roleOptions = $this->user()?->hasRole('admin')
            ? self::ROLE_OPTIONS
            : self::PUBLIC_ROLE_OPTIONS;

        return [
            'role' => ['required', 'string', 'in:'.implode(',', array_keys($roleOptions))],
            // Identitas nama/email/HP hanya untuk provisioning role
            // non-Poktan oleh Admin. Akun Poktan memakai identitas
            // kelompok taninya (nama/email dibuat otomatis).
            'name' => ['required_unless:role,poktan', 'nullable', 'string', 'min:3', 'max:150'],
            'email' => ['required_unless:role,poktan', 'nullable', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'phone' => [
                'required_unless:role,poktan',
                'nullable',
                'string',
                'max:20',
                'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/',
            ],
            'kelompok_tani_id' => [
                'required_if:role,poktan',
                'nullable',
                'integer',
                Rule::exists('ref_kelompok_tani', 'id')->where(function ($query): void {
                    $query->where('source', RefKelompokTani::SOURCE_DISBUN)
                        ->where('source_is_active', true)
                        ->where('is_verified', true)
                        ->where('sync_status', '!=', RefKelompokTani::SYNC_QUARANTINED)
                        ->whereNull('deleted_at');
                }),
                Rule::unique('users', 'kelompok_tani_id')->whereNotNull('kelompok_tani_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_unless' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama lengkap minimal 3 karakter.',
            'email.required_unless' => 'Email wajib diisi.',
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
            'phone.required_unless' => 'Nomor HP/WhatsApp wajib diisi.',
            'phone.regex' => 'Format nomor HP tidak valid. Contoh: 081234567890 atau +6281234567890.',
            'kelompok_tani_id.required_if' => 'Kelompok tani wajib dipilih untuk akun Poktan.',
            'kelompok_tani_id.exists' => 'Kelompok tani tidak ditemukan atau tidak aktif pada data referensi Disbun.',
            'kelompok_tani_id.unique' => 'Kelompok tani ini sudah memiliki akun. Silakan login memakai Kode Poktan tersebut.',
        ];
    }
}
