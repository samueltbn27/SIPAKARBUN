<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCfProvenance;
use App\Models\AturanCf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAturanCfRequest extends FormRequest
{
    use ValidatesCfProvenance;

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user?->hasAnyRole(['admin', 'operator_uptd'])) {
            return true;
        }

        $aturanCf = $this->route('aturanCf') ?? $this->route('aturan_cf');

        return $user?->hasRole('popt') === true
            && is_object($aturanCf)
            && $aturanCf->status === 'draft';
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['sometimes', 'required', 'integer', 'exists:penyakit,id'],
            'gejala_id' => ['sometimes', 'required', 'integer', 'exists:gejala,id'],
            'cf_pakar' => ['sometimes', 'required', 'numeric', 'between:-1,1'],
            'jenis_sumber' => ['nullable', 'string', Rule::in(AturanCf::SOURCE_TYPES)],
            'sumber' => ['nullable', 'string', 'max:150'],
            'pendekatan' => ['nullable', 'string', 'max:150'],
            'dasar_penentuan' => ['nullable', 'string'],
            'referensi_penulis' => ['nullable', 'string', 'max:150'],
            'referensi_tahun' => ['nullable', 'integer', 'between:1800,'.((int) date('Y'))],
            'referensi_url' => ['nullable', 'url', 'max:500'],
            'validator_nama' => ['nullable', 'string', 'max:150'],
            'validator_instansi' => ['nullable', 'string', 'max:150'],
            'tanggal_validasi' => ['nullable', 'date'],
            'status_validasi' => ['nullable', 'in:unvalidated,validated'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'penyakit_id.exists' => 'Penyakit yang dipilih tidak ditemukan.',
            'gejala_id.exists' => 'Gejala yang dipilih tidak ditemukan.',
            'cf_pakar.between' => 'Nilai CF pakar harus di antara -1 dan 1.',
            'jenis_sumber.in' => 'Jenis sumber CF tidak valid.',
            'sumber.max' => 'Sumber/referensi maksimal 150 karakter.',
            'pendekatan.max' => 'Pendekatan penentuan CF maksimal 150 karakter.',
            'referensi_tahun.integer' => 'Tahun referensi harus berupa angka.',
            'referensi_tahun.between' => 'Tahun referensi harus di antara 1800 dan tahun berjalan.',
            'referensi_url.url' => 'URL referensi tidak valid.',
            'status_validasi.in' => 'Status validasi harus unvalidated atau validated.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
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
            $status = $this->input('status', is_object($current) ? $current->status : AturanCf::STATUS_DRAFT);

            if ($status === AturanCf::STATUS_AKTIF && $penyakitId && $gejalaId) {
                $sudahAda = AturanCf::query()
                    ->where('penyakit_id', $penyakitId)
                    ->where('gejala_id', $gejalaId)
                    ->where('status', AturanCf::STATUS_AKTIF)
                    ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                    ->exists();

                if ($sudahAda) {
                    $validator->errors()->add(
                        'gejala_id',
                        'Sudah ada rule CF aktif lain untuk pasangan penyakit & gejala ini.'
                    );
                }
            }

            $this->validateCfProvenance($validator, $this->all(), is_object($current) ? $current : null);
        });
    }
}
