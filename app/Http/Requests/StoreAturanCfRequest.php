<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCfProvenance;
use App\Models\AturanCf;
use App\Models\CfMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAturanCfRequest extends FormRequest
{
    use ValidatesCfProvenance;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'operator_uptd', 'popt']) ?? false;
    }

    public function rules(): array
    {
        return [
            'penyakit_id' => ['required', 'integer', 'exists:penyakit,id'],
            'gejala_id' => ['required', 'integer', 'exists:gejala,id'],
            // Rentang -1.000 s.d 1.000 — ASUMSI, lihat catatan di
            // migration aturan_cf. Sesuaikan kalau pakar/pembimbing
            // menetapkan rentang berbeda (mis. 0 s.d 1 saja).
            'cf_pakar' => ['nullable', 'numeric', 'between:-1,1', 'required_without:expert_term'],
            'cf_method_id' => ['nullable', 'integer', 'exists:cf_methods,id'],
            'expert_term' => ['nullable', 'string', 'max:100'],
            'expert_rationale' => ['nullable', 'string', 'max:5000'],
            'expert_name' => ['nullable', 'string', 'max:150'],
            'expert_institution' => ['nullable', 'string', 'max:150'],
            'elicited_at' => ['nullable', 'date'],
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
     * Validasi tambahan: cegah dua rule AKTIF untuk pasangan
     * penyakit+gejala yang sama (boleh ada riwayat versi lama yang
     * nonaktif, tapi hanya satu yang aktif di satu waktu).
     * Ini sengaja tidak dipasang sebagai unique constraint di database
     * (lihat catatan versioning di migration aturan_cf), jadi
     * dicek manual di sini.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $status = $this->input('status', AturanCf::STATUS_DRAFT);

            if ($status === AturanCf::STATUS_AKTIF) {
                $sudahAda = AturanCf::query()
                    ->where('penyakit_id', $this->input('penyakit_id'))
                    ->where('gejala_id', $this->input('gejala_id'))
                    ->where('status', AturanCf::STATUS_AKTIF)
                    ->exists();

                if ($sudahAda) {
                    $validator->errors()->add(
                        'gejala_id',
                        'Sudah ada rule CF aktif untuk pasangan penyakit & gejala ini. Nonaktifkan rule lama dulu sebelum menambah yang baru.'
                    );
                }
            }

            if ($methodId = $this->input('cf_method_id')) {
                $method = CfMethod::find((int) $methodId);
                if ($method && ! $method->is_active) {
                    $validator->errors()->add('cf_method_id', 'Metode CF harus aktif untuk aturan baru.');
                }
            }

            $this->validateCfProvenance($validator, $this->all());
        });
    }

    protected function prepareForValidation(): void
    {
        $method = $this->input('cf_method_id')
            ? CfMethod::find($this->integer('cf_method_id'))
            : null;
        $term = $this->input('expert_term');
        $mapped = $method?->cfForTerm(is_string($term) ? $term : null);

        if ($mapped !== null) {
            // Nilai dari browser hanya preview. Nilai yang lolos validasi
            // selalu diturunkan ulang dari method + term di server.
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
