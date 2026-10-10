<?php

namespace App\Http\Requests;

use App\Models\CfMethod;

class UpdateCfMethodRequest extends StoreCfMethodRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $method = $this->route('cfMethod');

        // A method version already used by a published rule is immutable in
        // practice: create a new version instead of changing history.
        if ($method instanceof CfMethod && $method->aturanCf()->where('status', 'aktif')->exists()) {
            $rules['scale_definition'] = ['prohibited'];
            $rules['version'] = ['prohibited'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $method = $this->route('cfMethod');
        if (($method instanceof CfMethod) === false) {
            return;
        }
        if ($method->aturanCf()->where('status', 'aktif')->doesntExist()) {
            return;
        }

        $input = $this->all();
        $currentScale = collect($method->scaleOptions())->values()->all();
        $submittedScale = collect($input['scale_definition'] ?? [])->values()->all();
        if ($currentScale === $submittedScale) {
            unset($input['scale_definition']);
        }
        if ((string) ($input['version'] ?? '') === (string) $method->version) {
            unset($input['version']);
        }
        $this->replace($input);
    }
}
