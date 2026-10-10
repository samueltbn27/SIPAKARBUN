<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Shared validation and storage contract for final-report photos.
 *
 * The service is intentionally independent from HTTP workflow actions so the
 * POPT completion flow can reuse it in a later phase.
 */
class LaporanAkhirEvidenceService
{
    public const DIRECTORY = 'laporan-akhir';

    public const MAX_SIZE_KB = 5120;

    public const MIN_REQUIRED_FILES = 1;

    public function __construct(
        private readonly EvidenceFileHandler $evidenceFileHandler,
    ) {}

    /** @return array<int, string> */
    public function validationRules(): array
    {
        return ['file', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_SIZE_KB];
    }

    /** @return array{file_path:string, file_name:string, mime_type:string} */
    public function store(UploadedFile $file): array
    {
        return $this->evidenceFileHandler->store($file, self::DIRECTORY);
    }
}
