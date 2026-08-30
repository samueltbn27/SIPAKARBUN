<?php

namespace App\Services;

/**
 * Hasil fetch lengkap dari satu sumber referensi Disbun.
 * Dataset hanya boleh diteruskan ke sync setelah seluruh halaman berhasil.
 */
final class ReferenceFetchResult
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, int> $quarantineReasons
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $fetched,
        public readonly int $valid,
        public readonly int $quarantined,
        public readonly int $pages,
        public readonly array $quarantineReasons = [],
        public readonly ?int $total = null,
        public readonly ?int $countAll = null,
        public readonly int $uniqueExternalIds = 0,
        public readonly int $duplicateOccurrences = 0,
        public readonly int $duplicateExternalIdCount = 0,
        public readonly int $exactDuplicateOccurrences = 0,
        public readonly int $exactDuplicateIds = 0,
        public readonly int $conflictingDuplicateIds = 0,
        public readonly array $conflictingExternalIds = [],
        public readonly ?int $terminalShortPageStart = null,
        public readonly ?int $sourceExhaustedAt = null,
        public readonly ?float $sourceCompletionRatio = null,
        public readonly int $metadataGap = 0,
        public readonly bool $sourceWarning = false,
        public readonly array $warningReasons = [],
        public readonly ?array $order = null,
        public readonly int $pagesWithOverlap = 0,
        public readonly int $totalOverlappingOccurrences = 0,
    ) {}
}
