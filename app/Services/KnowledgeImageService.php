<?php

namespace App\Services;

use App\Support\PublicStorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $path = $file->store("knowledge/{$entity}", self::DISK);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Foto Knowledge gagal disimpan ke penyimpanan lokal.');
        }

        return $path;
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
        return PublicStorageUrl::make($path);
    }
}
