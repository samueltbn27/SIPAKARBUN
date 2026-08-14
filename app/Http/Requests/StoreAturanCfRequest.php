<?php

namespace App\Http\Requests;

use App\Models\AturanCf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAturanCfRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin & Pakar (Knowledge Manager) yang boleh mengelola
        // basis pengetahuan — sesuai RBAC Matrix PRD §24
        // ("Penyakit & gejala": C/R/U/D untuk Knowledge Manager, admin
        // untuk Admin).
        return $this->user()?->hasRole(['admin', 'operator_uptd', 'popt']) ?? false;
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['required', 'integer', 'exists:penyakit,id'],
            'gejala_id' => ['required', 'integer', 'exists:gejala,id'],
            // Rentang -1.000 s.d 1.000 — ASUMSI, lihat catatan di
            // migration aturan_cf. Sesuaikan kalau pakar/pembimbing
            // menetapkan rentang berbeda (mis. 0 s.d 1 saja).
            'cf_pakar' => ['required', 'numeric', 'between:-1,1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'penyakit_id.exists' => 'Penyakit yang dipilih tidak ditemukan.',
            'gejala_id.exists' => 'Gejala yang dipilih tidak ditemukan.',
            'cf_pakar.between' => 'Nilai CF pakar harus di antara -1 dan 1.',
        ];
    }

    /**
     * Validasi tambahan: cegah dua rule AKTIF untuk pasangan
     * penyakit+gejala yang sama (boleh ada riwayat versi lama yang
     * is_active = false, tapi hanya satu yang aktif di satu waktu).
     * Ini sengaja tidak dipasang sebagai unique constraint di database
     * (lihat catatan versioning di migration aturan_cf), jadi
     * dicek manual di sini.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isActive = $this->boolean('is_active', true);

            if (!$isActive) {
                return;
            }

            $sudahAda = AturanCf::query()
                ->where('penyakit_id', $this->input('penyakit_id'))
                ->where('gejala_id', $this->input('gejala_id'))
                ->where('is_active', true)
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add(
                    'gejala_id',
                    'Sudah ada rule CF aktif untuk pasangan penyakit & gejala ini. Nonaktifkan rule lama dulu sebelum menambah yang baru.'
                );
            }
        });
    }
}
