<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCfMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'version' => ['required', 'string', 'max:30'],
            'elicitation_question_template' => ['required', 'string', 'max:1000'],
            'scale_definition' => ['required', 'array', 'min:1'],
            'scale_text' => ['nullable', 'string', 'max:5000'],
            'scale_definition.*.term' => ['required', 'string', 'max:100'],
            'scale_definition.*.cf' => ['required', 'numeric', 'between:-1,1'],
            'reference_title' => ['nullable', 'string', 'max:255'],
            'reference_authors' => ['nullable', 'string', 'max:255'],
            'reference_year' => ['nullable', 'integer', 'between:1800,'.((int) date('Y'))],
            'reference_doi' => ['nullable', 'string', 'max:255'],
            'reference_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $text = $this->input('scale_text');
        if (! is_string($text) || trim($text) === '') {
            return;
        }

        $scale = collect(preg_split('/\R/', $text) ?: [])
            ->map(fn (string $line) => array_map('trim', explode('|', $line, 2)))
            ->filter(fn (array $parts) => count($parts) === 2 && $parts[0] !== '' && is_numeric($parts[1]))
            ->map(fn (array $parts) => ['term' => $parts[0], 'cf' => (float) $parts[1]])
            ->values()
            ->all();

        $this->merge(['scale_definition' => $scale]);
    }
}
