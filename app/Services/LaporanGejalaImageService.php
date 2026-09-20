<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaporanGejalaImageService
{
    private const PRIVATE_DISK = 'local';

    private const PRIVATE_PREFIX = 'laporan-gejala/';

    public function store(?UploadedFile $file): ?string
    {
        return $file?->store('laporan-gejala', self::PRIVATE_DISK);
    }

    public function delete(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, self::PRIVATE_PREFIX)) {
            return;
        }

        $disk = Storage::disk(self::PRIVATE_DISK);
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public function copyToKnowledge(?string $sourcePath): ?string
    {
        if ($sourcePath === null || ! str_starts_with($sourcePath, self::PRIVATE_PREFIX)) {
            return null;
        }

        $sourceDisk = Storage::disk(self::PRIVATE_DISK);
        if (! $sourceDisk->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetPath = 'knowledge/gejala/'.Str::uuid().($extension === '' ? '' : ".{$extension}");
        $stream = $sourceDisk->readStream($sourcePath);
        if (! is_resource($stream)) {
            return null;
        }

        $copied = Storage::disk('public')->writeStream($targetPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        if (! $copied) {
            Storage::disk('public')->delete($targetPath);

            return null;
        }

        return $targetPath;
    }
}
