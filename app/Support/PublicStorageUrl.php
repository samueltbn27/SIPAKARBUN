<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class PublicStorageUrl
{
    public static function make(?string $path, ?Request $request = null): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $storageUrl = Storage::disk('public')->url($path);
        $storagePath = parse_url($storageUrl, PHP_URL_PATH);

        if (! is_string($storagePath) || $storagePath === '') {
            return $storageUrl;
        }

        $request ??= request();
        $origin = $request instanceof Request ? $request->getSchemeAndHttpHost() : '';

        return $origin === ''
            ? $storageUrl
            : rtrim($origin, '/').'/'.ltrim($storagePath, '/');
    }
}
