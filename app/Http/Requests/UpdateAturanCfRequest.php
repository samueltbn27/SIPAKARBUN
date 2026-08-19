<?php

namespace App\Http\Requests;

use App\Models\AturanCf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAturanCfRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO(tahap 6 - Auth & Role): cek role POPT/Admin.
        return $this->user() !== null && $this->user()->hasRole(['admin', 'popt']);
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['sometimes', 'required', 'integer', 'exists:penyakit,id'],
            'gejala_id' => ['sometimes', 'required', 'integer', 'exists:gejala,id'],
            'cf_pakar' => ['sometimes', 'required', 'numeric', 'between:-1,1'],
            'status' => ['sometimes', Rule::in(['draft', 'aktif', 'nonaktif'])],
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
            $status = $this->input('status', is_object($current) ? $current->status : null);

            if ($status !== 'aktif' || ! $penyakitId || ! $gejalaId) {
                return;
            }

            $sudahAda = AturanCf::query()
                ->where('penyakit_id', $penyakitId)
                ->where('gejala_id', $gejalaId)
                ->where('status', 'aktif')
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
