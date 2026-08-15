<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception khusus integrasi Knowledge API (modul Mahasiswa 1).
 *
 * Dipisahkan dari exception bisnis diagnosis supaya controller/service
 * modul Mahasiswa 2 bisa menangkap kesalahan integrasi secara spesifik
 * (mis. Knowledge down, token salah, response berubah struktur) dan
 * memberi response yang wajar, bukan error 500 generik.
 */
class KnowledgeApiException extends RuntimeException
{
    public static function configurationError(string $message): self
    {
        return new self("Konfigurasi Knowledge API tidak lengkap: {$message}");
    }

    public static function serverError(int $status, string $body): self
    {
        return new self("Knowledge API merespons status {$status}: {$body}");
    }

    public static function networkError(Throwable $previous): self
    {
        return new self(
            'Gagal terhubung ke Knowledge API: '.$previous->getMessage(),
            0,
            $previous
        );
    }

    public static function invalidResponse(string $message): self
    {
        return new self("Response Knowledge API tidak valid: {$message}");
    }
}
