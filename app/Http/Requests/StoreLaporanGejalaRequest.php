<?php

namespace App\Http\Requests;

use App\Contracts\KomoditasReferensiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Throwable;

class StoreLaporanGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('poktan') ?? false;
    }

    public function rules(): array
    {
        return [
            'commodity_id' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'commodity_id.required' => 'Komoditas laporan tidak ditemukan. Pilih komoditas terlebih dahulu.',
            'description.required' => 'Ceritakan gejala yang Anda amati.',
            'description.min' => 'Deskripsi gejala minimal 10 karakter agar dapat ditinjau.',
            'image.image' => 'File bukti harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $id = $this->integer('commodity_id');
            if ($id < 1 || $validator->errors()->has('commodity_id')) {
                return;
            }

            try {
                $commodity = app(KomoditasReferensiClient::class)->find($id);
            } catch (Throwable) {
                $commodity = null;
            }

            if ($commodity === null || ! ($commodity['is_active'] ?? false)) {
                $validator->errors()->add('commodity_id', 'Komoditas tidak tersedia atau tidak aktif. Muat ulang halaman lalu coba lagi.');
            }
        });
    }
}
