<?php

namespace App\Support;

use App\Models\AturanCf;

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
        $requiresReference = in_array($sourceType, [
            AturanCf::SOURCE_LITERATURE,
            AturanCf::SOURCE_EXPERT_LITERATURE,
            AturanCf::SOURCE_TECHNICAL_GUIDELINE,
            AturanCf::SOURCE_RESEARCH,
        ], true);
        $errors = [];

        if ($status === AturanCf::STATUS_AKTIF) {
            if (! $sourceType) {
                $errors['jenis_sumber'] = 'Knowledge aktif wajib memiliki jenis sumber nilai CF.';
            }

            if (! $value('pendekatan')) {
                $errors['pendekatan'] = 'Knowledge aktif wajib memiliki metode penentuan nilai CF.';
            }

            if (! $value('dasar_penentuan')) {
                $errors['dasar_penentuan'] = 'Knowledge aktif wajib memiliki dasar atau alasan penentuan nilai CF.';
            }

            if ($requiresReference && ! $value('sumber')) {
                $errors['sumber'] = 'Jenis sumber ini wajib mencantumkan judul referensi sebelum dipublikasikan.';
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
