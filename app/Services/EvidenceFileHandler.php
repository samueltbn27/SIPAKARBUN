<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * EvidenceFileHandler — penyimpanan file bukti yang AMAN (M2).
 *
 * Latar belakang kontrak §22:
 *   - Nama file asli dari client TIDAK pernah dipakai sebagai path.
 *     Storage path memakai UUID yang di-generate di sini.
 *   - Ekstensi file TIDAK diambil dari `$file->extension()` / nama asli
 *     client (bisa disisipi misal `.php` walau MIME aslinya gambar).
 *     Ekstensi diturunkan dari MIME type yang SUDAH lolos validasi
 *     (whitelist), sehingga file tersimpan selalu memakai ekstensi yang
 *     sesuai isi file.
 *   - Nama tampilan (`file_name`) disanitasi: semua karakter selain
 *     [alnum, spasi, -, _] dibuang, path separator dibuang, dan dipotong
 *     ke panjang aman — mencegah path traversal / skrip pada response.
 */
class EvidenceFileHandler
{
    /** Whitelist MIME -> ekstensi file yang aman (jangan diperluas tanpa audit). */
    private const MIME_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_FILENAME_LENGTH = 120;

    /**
     * Simpan file bukti ke disk `public:<directory>` dengan nama UUID
     * dan MIME yang sudah tervalidasi.
     *
     * @return array{file_path:string, file_name:string, mime_type:string}
     */
    public function store(UploadedFile $file, string $directory = 'bukti_permohonan'): array
    {
        $mime = (string) $file->getMimeType();
        $extension = self::MIME_EXTENSION[$mime] ?? 'jpg';

        $storedName = Str::uuid()->toString().'.'.$extension;

        $filePath = $file->storeAs($directory, $storedName, ['disk' => 'public']);

        return [
            'file_path' => (string) $filePath,
            'file_name' => $this->safeDisplayName($file, $extension),
            'mime_type' => $mime,
        ];
    }

    /**
     * Nama tampilan aman: ambil nama dasar (tanpa direktori), buang semua
     * karakter selain huruf/angka/spasi/-/_, lalu potong ke panjang aman.
     */
    private function safeDisplayName(UploadedFile $file, string $extension): string
    {
        $original = (string) $file->getClientOriginalName();

        // Buang path traversal (.. / ..\) dan ambil bagian terakhir saja.
        $basename = preg_replace('#[/\\\\]+#', '_', $original) ?? $original;
        $basename = basename($basename);

        // Buang ekstensi asli client supaya tidak ikut tampil / membingungkan.
        $withoutExt = pathinfo($basename, PATHINFO_FILENAME);

        // Sanitasi: hanya huruf, angka, spasi, '-', '_'.
        $name = preg_replace('/[^A-Za-z0-9 _\-]/u', '', $withoutExt) ?? '';
        $name = trim((string) $name);

        if ($name === '') {
            $name = 'bukti';
        }

        $name = Str::limit($name, self::MAX_FILENAME_LENGTH, '');

        return $name.'.'.$extension;
    }
}
