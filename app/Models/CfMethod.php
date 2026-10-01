<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfMethod extends Model
{
    use HasFactory;

    public const EXPERT_METHOD_NAME = 'Expert Elicitation – Linguistic CF Scale';

    public const SIMULATION_METHOD_NAME = 'Simulation / UAT';

    public const STANDARD_VERSION = '1.0';

    protected $table = 'cf_methods';

    protected $fillable = [
        'name',
        'description',
        'version',
        'elicitation_question_template',
        'scale_definition',
        'reference_title',
        'reference_authors',
        'reference_year',
        'reference_doi',
        'reference_url',
        'is_active',
    ];

    protected $casts = [
        'scale_definition' => 'array',
        'reference_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function aturanCf(): HasMany
    {
        return $this->hasMany(AturanCf::class, 'cf_method_id');
    }

    public function scopeAktifSaja(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query
            ->where('name', self::EXPERT_METHOD_NAME)
            ->where('version', self::STANDARD_VERSION);
    }

    public function isStandard(): bool
    {
        return $this->name === self::EXPERT_METHOD_NAME
            && $this->version === self::STANDARD_VERSION;
    }

    /** @return array<int, array{term:string, cf:float}> */
    public function scaleOptions(): array
    {
        return collect($this->scale_definition ?? [])
            ->map(function (mixed $option): ?array {
                if (! is_array($option)) {
                    return null;
                }

                $term = $option['term'] ?? $option['label'] ?? $option['name'] ?? null;
                $cf = $option['cf'] ?? $option['value'] ?? null;

                if (! is_string($term) || $term === '' || ! is_numeric($cf)) {
                    return null;
                }

                return ['term' => $term, 'cf' => (float) $cf];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function cfForTerm(?string $term): ?float
    {
        if ($term === null || trim($term) === '') {
            return null;
        }

        $match = collect($this->scaleOptions())
            ->first(fn (array $option): bool => mb_strtolower($option['term']) === mb_strtolower(trim($term)));

        return $match['cf'] ?? null;
    }

    public function hasTerm(?string $term): bool
    {
        return $this->cfForTerm($term) !== null;
    }

    public function referenceLabel(): string
    {
        if (! $this->reference_title) {
            return 'Belum tersedia';
        }

        $parts = [$this->reference_title];
        if ($this->reference_authors) {
            $parts[] = $this->reference_authors;
        }
        if ($this->reference_year) {
            $parts[] = (string) $this->reference_year;
        }

        return implode(' · ', $parts);
    }
}
