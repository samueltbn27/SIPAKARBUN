<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Shared local storage lifecycle for Knowledge photos.
 *
 * Paths are deliberately constrained to the application's public disk and
 * knowledge/ prefix so replacing/deleting a record cannot remove arbitrary
 * files or external URLs.
 */
class KnowledgeImageService
{
    private const DISK = 'public';

    public function store(?UploadedFile $file, string $entity): ?string
    {
        return $file?->store("knowledge/{$entity}", self::DISK);
    }

    public function replace(UploadedFile $file, ?string $oldPath, string $entity): string
    {
        $newPath = $this->store($file, $entity);
        $this->deleteIfLocal($oldPath);

        return $newPath;
    }

    public function deleteIfLocal(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, 'knowledge/')) {
            return;
        }

        $disk = Storage::disk(self::DISK);
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
