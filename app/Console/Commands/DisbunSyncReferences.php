<?php

namespace App\Console\Commands;

use App\Services\DisbunReferenceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisbunSyncReferences extends Command
{
    protected $signature = 'disbun:sync-references';

    protected $description = 'Synchronize complete Disbun commodity and farmer-group references locally';

    public function handle(DisbunReferenceSyncService $sync): int
    {
        $this->info('Disbun Reference Sync');
        $this->line('Fetching Komoditas...');

        try {
            $report = $sync->syncAllReferences(function (string $dataset, int $start, int $count): void {
                $this->line("Page {$dataset} start={$start} ({$count} records)");
            });
        } catch (Throwable $e) {
            $this->error('Reference Sync: FAILED');
            $this->error($e->getMessage());
            Log::error('Scheduled Disbun reference sync failed; last-good references retained.', [
                'result' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $hasSourceWarning = false;
        $hasMetadataMismatch = false;
        $hasSourceDataQualityWarning = false;
        foreach (['komoditas' => 'Komoditas', 'kelompok_tani' => 'Kelompok Tani'] as $key => $label) {
            $stats = $report[$key];
            $this->newLine();
            $this->info($label.':');
            if ($key === 'kelompok_tani') {
                $this->line('API reported count: '.($stats['total'] ?? 0));
                $this->line('API reported count_all: '.($stats['count_all'] ?? 0));
                $this->line('Raw fetched: '.($stats['fetched'] ?? 0));
                $this->line('Unique records: '.($stats['unique_external_ids'] ?? 0));
                $this->line('Duplicate external IDs: '.($stats['duplicate_external_id_count'] ?? 0));
                $this->line('Exact duplicate occurrences: '.($stats['exact_duplicate_occurrences'] ?? 0));
                $this->line('Conflicting duplicates: '.($stats['conflicting_duplicate_ids'] ?? 0));
                $this->line('Terminal short page start: '.($stats['terminal_short_page_start'] ?? 'none'));
                $this->line('Source exhausted at start: '.($stats['source_exhausted_at'] ?? 'none'));
                $this->line('Source completion ratio: '.($stats['source_completion_ratio'] === null
                    ? 'n/a'
                    : number_format((float) $stats['source_completion_ratio'] * 100, 2).'%'));
                $this->line('Metadata gap: '.($stats['metadata_gap'] ?? 0));
                $this->line('Pages with overlapping IDs: '.($stats['pages_with_overlap'] ?? 0));
                $this->line('Total overlapping occurrences: '.($stats['total_overlapping_occurrences'] ?? 0));
                $this->line('Order: '.json_encode($stats['order'] ?? null, JSON_UNESCAPED_SLASHES));
            } else {
                $this->line('Fetched: '.($stats['fetched'] ?? 0));
            }
            $this->line('Valid: '.($stats['valid'] ?? 0));
            $this->line('Quarantined: '.($stats['quarantined'] ?? 0));
            if ($key !== 'kelompok_tani' && ($stats['total'] ?? null) !== null) {
                $this->line('Total: '.$stats['total']);
            }
            if ($key !== 'kelompok_tani' && ($stats['count_all'] ?? null) !== null) {
                $this->line('Count all: '.$stats['count_all']);
            }
            foreach (($stats['quarantine_reasons'] ?? []) as $reason => $count) {
                $this->line("  {$reason}: {$count}");
            }
            $this->line('Upserted: '.($stats['upserted'] ?? 0));
            $this->line('Updated: '.($stats['updated'] ?? 0));
            $this->line('Unchanged: '.max(0, (int) ($stats['upserted'] ?? 0) - (int) ($stats['updated'] ?? 0)));
            if (($stats['unresolved_commodity'] ?? 0) > 0) {
                $this->line('Unresolved commodity mapping: '.$stats['unresolved_commodity']);
            }
            $this->line('Local Disbun: '.($stats['local'] ?? 0));

            if (($stats['source_warning'] ?? false) === true) {
                $hasSourceWarning = true;
                foreach (($stats['warning_reasons'] ?? []) as $warning) {
                    $hasMetadataMismatch = $hasMetadataMismatch || $warning === 'metadata_count_mismatch';
                    $hasSourceDataQualityWarning = $hasSourceDataQualityWarning
                        || in_array($warning, ['exact_duplicate_source_rows', 'page_overlap'], true);
                    $this->warn('Source warning: '.$warning);
                }
            }
        }

        $this->newLine();
        if ($hasSourceWarning) {
            if ($hasMetadataMismatch) {
                $this->warn('WARNING: Disbun metadata count does not match records actually served by API.');
            }
            if ($hasSourceDataQualityWarning) {
                $this->warn('WARNING: Exact duplicate rows or overlapping source pages were detected and reconciled.');
            }
            $this->warn('Reference Sync: SUCCESS WITH SOURCE WARNING');
        } else {
            $this->info('Reference Sync: SUCCESS');
        }

        Log::log($hasSourceWarning ? 'warning' : 'info', 'Disbun reference sync completed.', [
            'result' => $hasSourceWarning ? 'success_with_source_warning' : 'success',
            'komoditas_fetched' => (int) ($report['komoditas']['fetched'] ?? 0),
            'poktan_metadata_count' => (int) ($report['kelompok_tani']['total'] ?? 0),
            'poktan_raw_fetched' => (int) ($report['kelompok_tani']['fetched'] ?? 0),
            'poktan_unique' => (int) ($report['kelompok_tani']['unique_external_ids'] ?? 0),
            'source_completion_ratio' => $report['kelompok_tani']['source_completion_ratio'] ?? null,
            'unresolved_commodity' => (int) ($report['kelompok_tani']['unresolved_commodity'] ?? 0),
            'source_warnings' => $report['kelompok_tani']['warning_reasons'] ?? [],
        ]);

        return self::SUCCESS;
    }
}
