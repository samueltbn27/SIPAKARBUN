<?php

namespace App\Http\Requests;

use App\Models\Gejala;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGejalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user?->hasAnyRole(['admin', 'operator_uptd'])) {
            return true;
        }

        $gejala = $this->route('gejala');

        return $user?->hasRole('popt') === true
            && is_object($gejala)
            && $gejala->status === 'draft';
    }

    public function rules(): array
    {
        $gejalaId = $this->route('gejala')?->id ?? $this->route('gejala');

        return [
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('gejala', 'kode')->ignore($gejalaId)],
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'kriteria_observasi' => ['nullable', 'string'],
            'metode_pengamatan' => ['nullable', 'string', 'max:150'],
            'referensi_jenis' => ['nullable', 'string', Rule::in(Gejala::REFERENSI_JENIS)],
            'referensi_judul' => ['nullable', 'string', 'max:200'],
            'referensi_penulis' => ['nullable', 'string', 'max:150'],
            'referensi_tahun' => ['nullable', 'integer', 'between:1800,'.((int) date('Y'))],
            'referensi_url' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'in:draft,aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode gejala sudah dipakai, gunakan kode lain.',
            'status.in' => 'Status harus draft, aktif, atau nonaktif.',
            'referensi_jenis.in' => 'Jenis referensi harus pedoman, literatur, jurnal, pakar, atau lainnya.',
            'referensi_tahun.integer' => 'Tahun referensi harus berupa angka.',
            'referensi_tahun.between' => 'Tahun referensi harus di antara 1800 dan tahun berjalan.',
            'referensi_url.url' => 'URL referensi tidak valid.',
        ];
    }
}
