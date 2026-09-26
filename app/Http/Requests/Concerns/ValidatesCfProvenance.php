<?php

namespace App\Http\Requests\Concerns;

use App\Models\AturanCf;
use App\Support\CfProvenance;
use Illuminate\Validation\Validator;

trait ValidatesCfProvenance
{
    protected function validateCfProvenance(Validator $validator, array $input, ?AturanCf $existing = null): void
    {
        if (($input['status_validasi'] ?? null) === AturanCf::VALIDATION_VALIDATED
            && $this->user()?->hasRole('popt')) {
            $validator->errors()->add(
                'status_validasi',
                'POPT dapat menyusun draft, sedangkan status validasi hanya dapat ditetapkan oleh Admin atau Operator UPTD.'
            );
        }

        foreach (CfProvenance::errors($input, $existing) as $field => $message) {
            $validator->errors()->add($field, $message);
        }
    }
}
