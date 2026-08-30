<?php

namespace App\Console\Commands;

use App\Services\HttpKelompokTaniReferensiClient;
use Illuminate\Console\Command;

class DisbunAuditKelompokTani extends Command
{
    protected $signature = 'disbun:audit-kelompok-tani';

    protected $description = 'Audit Disbun farmer-group pagination integrity without changing local data';

    public function handle(HttpKelompokTaniReferensiClient $client): int
    {
        $this->info('Disbun Pagination Integrity Audit');
        $this->line('Mode: READ ONLY (database tidak diubah)');

        $audit = $client->auditPagination(function (array $page): void {
            $this->line(sprintf(
                'Page request start=%d limit=%d | response start=%d limit=%d count=%d count_all=%d | records=%d first_id=%s last_id=%s',
                $page['requested_start'],
                $page['requested_limit'],
                $page['response_start'],
                $page['response_limit'],
                $page['response_count'],
                $page['response_count_all'],
                $page['records_returned'],
                $page['first_external_id'] ?? '-',
                $page['last_external_id'] ?? '-',
            ));
        });

        $this->newLine();
        $this->info('API Metadata');
        $this->line('count: '.($audit['count'] ?? 'unknown'));
        $this->line('count_all: '.($audit['count_all'] ?? 'unknown'));
        $this->line('limit: '.$audit['limit']);
        $this->line('order: '.json_encode($audit['order'], JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info('Fetch Summary');
        $this->line('Expected records: '.($audit['count'] ?? 'unknown'));
        $this->line('Raw fetched records: '.$audit['raw_fetched']);
        $this->line('Unique external IDs: '.$audit['unique_external_ids']);
        $this->line('Duplicate occurrences: '.$audit['duplicate_occurrences']);
        $this->line('Duplicate external ID count: '.$audit['duplicate_external_id_count']);
        $this->line('Missing expected amount: '.($audit['missing_expected_amount'] ?? 'unknown'));
        $this->line('Expected pages: '.($audit['expected_pages'] ?? 'unknown'));
        $this->line('Successful pages: '.$audit['successful_pages']);
        $this->line('Repeated adjacent page sequences: '.count($audit['duplicated_page_pairs']));
        $this->line('First anomalous start: '.($audit['first_anomalous_start'] ?? 'none'));
        foreach ($audit['duplicated_page_pairs'] as $pair) {
            $this->line("Repeated page IDs: start {$pair['left_start']} == {$pair['right_start']}");
        }

        $this->newLine();
        $this->info('Overlap Analysis');
        foreach ($audit['overlaps'] as $overlap) {
            $this->line("page {$overlap['left_start']} vs {$overlap['right_start']} overlap = {$overlap['count']}");
        }
        $this->line('Pages with overlapping IDs: '.$audit['pages_with_overlap']);
        $this->line('Total overlapping occurrences: '.$audit['total_overlapping_occurrences']);

        $this->newLine();
        $this->info('Duplicate Classification');
        $this->line('Exact duplicate IDs: '.$audit['exact_duplicate_ids']);
        $this->line('Conflicting duplicate IDs: '.$audit['conflicting_duplicate_ids']);
        foreach ($audit['duplicate_examples'] as $duplicate) {
            $this->line(sprintf(
                'external_id=%s occurrences=%d classification=%s page_starts=%s',
                $duplicate['external_id'],
                $duplicate['occurrence_count'],
                strtoupper($duplicate['classification']),
                implode(',', $duplicate['page_starts']),
            ));
        }

        if ($audit['metadata_changes'] !== []) {
            $this->warn('Source metadata changed during fetch: '.json_encode($audit['metadata_changes'], JSON_UNESCAPED_SLASHES));
        }
        if ($audit['request_failure'] !== null) {
            $this->error('Request failure: '.$audit['request_failure']['message']);
        }

        $ready = $audit['request_failure'] === null
            && $audit['metadata_changes'] === []
            && $audit['missing_expected_amount'] === 0
            && $audit['conflicting_duplicate_ids'] === 0;

        $this->newLine();
        $this->{$ready ? 'info' : 'error'}('Ready for complete sync: '.($ready ? 'YES' : 'NO'));

        return $ready ? self::SUCCESS : self::FAILURE;
    }
}
