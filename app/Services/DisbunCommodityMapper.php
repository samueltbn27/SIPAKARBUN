<?php

namespace App\Services;

use App\Models\RefKomoditas;
use Illuminate\Support\Collection;

/**
 * Central, auditable mapping policy between raw Poktan commodity names and
 * the independent Disbun commodity master endpoint. Numeric source IDs are
 * deliberately never used as cross-endpoint foreign keys.
 */
final class DisbunCommodityMapper
{
    /** @var array<string, string> */
    private const APPROVED_ALIASES = [
        'panili' => 'vanili',
        'kelapa dalam' => 'kelapa',
        'sereh wangi' => 'seraiwangi',
    ];

    /** @var array<int, string> */
    private const AMBIGUOUS_NAMES = ['kopi', 'jarak'];

    /** @var array<int, string> */
    private const SOURCE_QUALITY_NAMES = ['lainnya'];

    /** @return Collection<string, RefKomoditas> */
    public function index(Collection $masters): Collection
    {
        return $masters->keyBy(fn (RefKomoditas $master): string => $this->normalize($master->nama));
    }

    /** @param Collection<string, RefKomoditas> $masterByName */
    public function resolve(?string $rawName, Collection $masterByName): ?RefKomoditas
    {
        $normalized = $this->normalize($rawName);
        if ($normalized === '') {
            return null;
        }

        $target = self::APPROVED_ALIASES[$normalized] ?? $normalized;

        return $masterByName->get($target);
    }

    /**
     * @param Collection<string, RefKomoditas> $masterByName
     * @return array{normalized_name:string,candidate_exact_master:?string,candidate_alias:?string,classification:string,target_id:?int}
     */
    public function classify(?string $rawName, Collection $masterByName): array
    {
        $normalized = $this->normalize($rawName);
        $exact = $masterByName->get($normalized);
        $aliasTarget = self::APPROVED_ALIASES[$normalized] ?? null;
        $alias = $aliasTarget === null ? null : $masterByName->get($aliasTarget);

        if ($exact !== null) {
            $classification = 'A_SAFE_EXACT_NORMALIZATION';
            $targetId = (int) $exact->id;
        } elseif ($alias !== null) {
            $classification = 'B_SAFE_EXPLICIT_ALIAS';
            $targetId = (int) $alias->id;
        } elseif (in_array($normalized, self::SOURCE_QUALITY_NAMES, true) || $normalized === '') {
            $classification = 'D_SOURCE_DATA_QUALITY';
            $targetId = null;
        } elseif (in_array($normalized, self::AMBIGUOUS_NAMES, true)) {
            $classification = 'E_AMBIGUOUS';
            $targetId = null;
        } else {
            $classification = 'C_MASTER_MISSING';
            $targetId = null;
        }

        return [
            'normalized_name' => $normalized,
            'candidate_exact_master' => $exact?->nama,
            'candidate_alias' => $alias?->nama,
            'classification' => $classification,
            'target_id' => $targetId,
        ];
    }

    /** @return array<string, string> */
    public function aliases(): array
    {
        return self::APPROVED_ALIASES;
    }

    public function normalize(mixed $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', ' ', trim((string) $value)));
    }
}
