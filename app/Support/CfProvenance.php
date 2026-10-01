<?php

namespace App\Support;

use App\Models\AturanCf;
use App\Models\CfMethod;

class CfProvenance
{
    public static function errors(array $input, ?AturanCf $existing = null): array
    {
        $value = static function (string $field) use ($input, $existing): mixed {
            $resolved = array_key_exists($field, $input)
                ? $input[$field]
                : $existing?->getAttribute($field);

            return $resolved === '' ? null : $resolved;
        };

        $status = $value('status') ?? AturanCf::STATUS_DRAFT;
        $validationStatus = $value('status_validasi') ?? AturanCf::VALIDATION_UNVALIDATED;
        $sourceType = $value('jenis_sumber');
        $methodId = $value('cf_method_id');
        $method = $methodId ? CfMethod::find((int) $methodId) : null;
        $expertTerm = $value('expert_term');
        $requiresReference = in_array($sourceType, [
            AturanCf::SOURCE_LITERATURE,
            AturanCf::SOURCE_EXPERT_LITERATURE,
            AturanCf::SOURCE_TECHNICAL_GUIDELINE,
            AturanCf::SOURCE_RESEARCH,
        ], true);
        $errors = [];

        if ($methodId && ! $method) {
            $errors['cf_method_id'] = 'Metode CF yang dipilih tidak ditemukan.';
        }
        if ($method && $expertTerm && ! $method->hasTerm((string) $expertTerm)) {
            $errors['expert_term'] = 'Tingkat keyakinan tidak tersedia pada skala metode CF yang dipilih.';
        }
        if ($method && $expertTerm && $method->hasTerm((string) $expertTerm)) {
            $mapped = $method->cfForTerm((string) $expertTerm);
            if ((float) $value('cf_pakar') !== (float) $mapped) {
                $errors['cf_pakar'] = 'Nilai CF tidak sesuai dengan pemetaan metode dan tingkat keyakinan.';
            }
            if ($method->isStandard() && (float) $mapped <= 0) {
                $errors['expert_term'] = 'Metode CF baku hanya menerima dukungan positif. Jangan buat hubungan untuk gejala netral atau tidak mendukung.';
            }
        }

        if ($status === AturanCf::STATUS_AKTIF) {
            if (! $sourceType) {
                $errors['jenis_sumber'] = 'Knowledge aktif wajib memiliki jenis sumber nilai CF.';
            }

            if (! $value('pendekatan') && ! $method) {
                $errors['pendekatan'] = 'Knowledge aktif wajib memiliki metode penentuan nilai CF.';
            }

            if (! $value('dasar_penentuan') && ! $value('expert_rationale')) {
                $errors['dasar_penentuan'] = 'Knowledge aktif wajib memiliki dasar atau alasan penentuan nilai CF.';
            }

            if ($requiresReference && ! $value('sumber')) {
                $errors['sumber'] = 'Jenis sumber ini wajib mencantumkan judul referensi sebelum dipublikasikan.';
            }

            // Rules created with the documented expert method must carry the
            // complete elicitation record before they can enter diagnosis.
            // Legacy/simulation rules remain publishable for compatibility.
            $isSimulation = $method?->name === CfMethod::SIMULATION_METHOD_NAME
                || (! $method && $sourceType === AturanCf::SOURCE_SIMULATION);
            if ($method && ! $method->is_active && (! $existing || (int) $existing->cf_method_id !== (int) $method->id)) {
                $errors['cf_method_id'] = 'Metode CF tidak aktif dan tidak dapat dipakai untuk aturan baru.';
            }
            if ($method && ! $isSimulation) {
                foreach ([
                    'cf_method_id' => 'Pilih metode CF sebelum memublikasikan aturan.',
                    'expert_term' => 'Catat tingkat keyakinan pakar sebelum memublikasikan aturan.',
                    'expert_rationale' => 'Rationale pakar wajib diisi sebelum memublikasikan aturan.',
                    'expert_name' => 'Nama pakar penilai wajib diisi sebelum memublikasikan aturan.',
                    'elicited_at' => 'Tanggal elicitation wajib diisi sebelum memublikasikan aturan.',
                ] as $field => $message) {
                    if (! $value($field)) {
                        $errors[$field] = $message;
                    }
                }
            }
        }

        if ($validationStatus === AturanCf::VALIDATION_VALIDATED) {
            if (! $value('validator_nama')) {
                $errors['validator_nama'] = 'Aturan CF tervalidasi wajib mencantumkan nama validator.';
            }

            if (! $value('tanggal_validasi')) {
                $errors['tanggal_validasi'] = 'Aturan CF tervalidasi wajib mencantumkan tanggal validasi.';
            }

            if ($requiresReference && ! $value('sumber')) {
                $errors['sumber'] = 'Aturan tervalidasi dengan sumber referensi wajib mencantumkan judul referensi.';
            }
        }

        return $errors;
    }
}
