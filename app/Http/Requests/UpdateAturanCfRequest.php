<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCfProvenance;
use App\Models\AturanCf;
use App\Models\CfMethod;
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
            'cf_pakar' => ['sometimes', 'nullable', 'numeric', 'between:-1,1', 'required_without:expert_term'],
            'cf_method_id' => ['sometimes', 'nullable', 'integer', 'exists:cf_methods,id'],
            'expert_term' => ['sometimes', 'nullable', 'string', 'max:100'],
            'expert_rationale' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'expert_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'expert_institution' => ['sometimes', 'nullable', 'string', 'max:150'],
            'elicited_at' => ['sometimes', 'nullable', 'date'],
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
            'cf_pakar.required_without' => 'Isi tingkat keyakinan pakar atau nilai CF untuk data legacy/simulasi.',
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

            if ($methodId = $this->input('cf_method_id')) {
                $method = CfMethod::find((int) $methodId);
                $sameHistoricalMethod = is_object($current) && (int) $current->cf_method_id === (int) $methodId;
                if ($method && ! $method->is_active && ! $sameHistoricalMethod) {
                    $validator->errors()->add('cf_method_id', 'Metode CF harus aktif untuk aturan baru.');
                }
            }

            $this->validateCfProvenance($validator, $this->all(), is_object($current) ? $current : null);
        });
    }

    protected function prepareForValidation(): void
    {
        $current = $this->route('aturanCf') ?? $this->route('aturan_cf');
        $methodId = $this->input('cf_method_id', is_object($current) ? $current->cf_method_id : null);
        $term = $this->input('expert_term', is_object($current) ? $current->expert_term : null);
        $method = $methodId ? CfMethod::find((int) $methodId) : null;
        $mapped = $method?->cfForTerm(is_string($term) ? $term : null);

        if ($mapped !== null) {
            $this->merge(['cf_pakar' => $mapped]);
        }

        if ($method && ! $this->filled('jenis_sumber')) {
            $this->merge([
                'jenis_sumber' => $method->name === CfMethod::SIMULATION_METHOD_NAME
                    ? AturanCf::SOURCE_SIMULATION
                    : AturanCf::SOURCE_EXPERT,
                'pendekatan' => $this->input('pendekatan') ?: $method->name,
            ]);
        }
    }
}
