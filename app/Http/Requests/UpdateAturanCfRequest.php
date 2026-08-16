<?php

namespace App\Http\Requests;

use App\Models\AturanCf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAturanCfRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role Pakar/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'pakar']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['sometimes', 'required', 'integer', 'exists:penyakit,id'],
            'gejala_id' => ['sometimes', 'required', 'integer', 'exists:gejala,id'],
            'cf_pakar' => ['sometimes', 'required', 'numeric', 'between:-1,1'],
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
     * Sama seperti StoreAturanCfRequest: cegah dua rule aktif untuk
     * pasangan penyakit+gejala yang sama, KECUALI record yang sedang
     * diedit sendiri.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $current = $this->route('aturanCf') ?? $this->route('aturan_cf');
            $currentId = is_object($current) ? $current->id : $current;

            $penyakitId = $this->input('penyakit_id', is_object($current) ? $current->penyakit_id : null);
            $gejalaId = $this->input('gejala_id', is_object($current) ? $current->gejala_id : null);
            $isActive = $this->boolean('is_active', is_object($current) ? (bool) $current->is_active : true);

            if (! $isActive || ! $penyakitId || ! $gejalaId) {
                return;
            }

            $sudahAda = AturanCf::query()
                ->where('penyakit_id', $penyakitId)
                ->where('gejala_id', $gejalaId)
                ->where('is_active', true)
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add(
                    'gejala_id',
                    'Sudah ada rule CF aktif lain untuk pasangan penyakit & gejala ini.'
                );
            }
        });
    }
}
