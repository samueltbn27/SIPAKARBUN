<?php

namespace App\Http\Requests;

use App\Services\LaporanAkhirEvidenceService;
use Illuminate\Foundation\Http\FormRequest;

class CompleteHandlingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('popt') === true;
    }

    public function rules(): array
    {
        return [
            'ringkasan_tindakan' => ['required', 'string', 'max:10000'],
            'hasil_penanganan' => ['required', 'string', 'max:10000'],
            'rekomendasi' => ['required', 'string', 'max:10000'],
            'catatan_tambahan' => ['nullable', 'string', 'max:10000'],
            'photos' => ['required', 'array', 'min:'.LaporanAkhirEvidenceService::MIN_REQUIRED_FILES, 'max:5'],
            'photos.*' => app(LaporanAkhirEvidenceService::class)->validationRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'ringkasan_tindakan.required' => 'Ringkasan tindakan wajib diisi.',
            'hasil_penanganan.required' => 'Hasil penanganan wajib diisi.',
            'rekomendasi.required' => 'Rekomendasi atau tindak lanjut wajib diisi.',
            'photos.required' => 'Minimal satu foto dokumentasi wajib diunggah.',
            'photos.min' => 'Minimal satu foto dokumentasi wajib diunggah.',
            'photos.max' => 'Maksimal lima foto dokumentasi dapat diunggah.',
            'photos.*.mimes' => 'Foto harus berformat JPEG, PNG, atau WebP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ];
    }
}
