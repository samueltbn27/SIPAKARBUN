<?php

namespace App\Console\Commands;

use App\Models\RefKelompokTani;
use App\Models\RefKomoditas;
use App\Services\DisbunCommodityMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DisbunAuditCommodityMappings extends Command
{
    protected $signature = 'disbun:audit-commodity-mappings
        {--apply-safe-aliases : Apply only A/B mappings to existing local unresolved references}
        {--csv= : Optional relative CSV path on the local storage disk}';

    protected $description = 'Audit unique unresolved Disbun Poktan commodity names without fuzzy matching';

    public function handle(DisbunCommodityMapper $mapper): int
    {
        $before = $this->audit($mapper);
        $this->render('Before mapping', $before);

        if ((string) $this->option('csv') !== '') {
            $path = ltrim((string) $this->option('csv'), '/');
            Storage::disk('local')->put($path, $this->csv($before['rows']));
            $this->line('CSV: '.Storage::disk('local')->path($path));
        }

        if ($this->option('apply-safe-aliases')) {
            $resolved = $this->applySafeMappings($before['rows']);
            $after = $this->audit($mapper);
            $this->newLine();
            $this->info("Safely remapped: {$resolved}");
            $this->render('After mapping', $after);
        }

        return self::SUCCESS;
    }

    /** @return array{total:int,unique:int,rows:array<int,array<string,mixed>>} */
    private function audit(DisbunCommodityMapper $mapper): array
    {
        $masters = RefKomoditas::query()->disbun()->get();
        $masterByName = $mapper->index($masters);
        $groups = RefKelompokTani::query()
            ->where('source', RefKelompokTani::SOURCE_DISBUN)
            ->where('commodity_mapping_status', 'unresolved')
            ->selectRaw("COALESCE(jenis_komoditi, external_commodity_name, '') as raw_name, COUNT(*) as poktan_count")
            ->groupBy('raw_name')
            ->orderByDesc('poktan_count')
            ->get();

        $rows = $groups->map(function ($group) use ($mapper, $masterByName): array {
            return [
                'raw_name' => (string) $group->raw_name,
                ...$mapper->classify((string) $group->raw_name, $masterByName),
                'poktan_count' => (int) $group->poktan_count,
            ];
        })->all();

        return [
            'total' => (int) $groups->sum('poktan_count'),
            'unique' => count($rows),
            'rows' => $rows,
        ];
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function applySafeMappings(array $rows): int
    {
        return DB::transaction(function () use ($rows): int {
            $resolved = 0;
            foreach ($rows as $row) {
                if (! in_array($row['classification'], ['A_SAFE_EXACT_NORMALIZATION', 'B_SAFE_EXPLICIT_ALIAS'], true)
                    || $row['target_id'] === null) {
                    continue;
                }

                $rawName = (string) $row['raw_name'];
                $resolved += RefKelompokTani::query()
                    ->where('source', RefKelompokTani::SOURCE_DISBUN)
                    ->where('commodity_mapping_status', 'unresolved')
                    ->where(function ($query) use ($rawName): void {
                        $query->where('jenis_komoditi', $rawName)
                            ->orWhere(function ($fallback) use ($rawName): void {
                                $fallback->whereNull('jenis_komoditi')
                                    ->where('external_commodity_name', $rawName);
                            });
                    })
                    ->update([
                        'commodity_ref_id' => (int) $row['target_id'],
                        'commodity_mapping_status' => 'mapped',
                        'quarantine_reason' => null,
                    ]);
            }

            return $resolved;
        });
    }

    /** @param array{total:int,unique:int,rows:array<int,array<string,mixed>>} $audit */
    private function render(string $title, array $audit): void
    {
        $this->info($title);
        $this->line('Unresolved Poktan: '.$audit['total']);
        $this->line('Unique raw names: '.$audit['unique']);
        $this->table(
            ['Raw name', 'Normalized', 'Poktan', 'Exact master', 'Alias candidate', 'Classification'],
            array_map(fn (array $row): array => [
                $row['raw_name'] !== '' ? $row['raw_name'] : '(empty)',
                $row['normalized_name'],
                $row['poktan_count'],
                $row['candidate_exact_master'] ?? '-',
                $row['candidate_alias'] ?? '-',
                $row['classification'],
            ], $audit['rows']),
        );
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function csv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['raw_name', 'normalized_name', 'poktan_count', 'candidate_exact_master', 'candidate_alias', 'classification']);
        foreach ($rows as $row) {
            fputcsv($stream, [
                $this->csvSafe((string) $row['raw_name']),
                $this->csvSafe((string) $row['normalized_name']),
                (int) $row['poktan_count'],
                $this->csvSafe((string) ($row['candidate_exact_master'] ?? '')),
                $this->csvSafe((string) ($row['candidate_alias'] ?? '')),
                $row['classification'],
            ]);
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return (string) $contents;
    }

    private function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
