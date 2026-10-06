<?php

namespace App\Enums;

enum TingkatKeyakinan: string
{
    case TIDAK = 'tidak';
    case TIDAK_TAHU = 'tidak_tahu';
    case SEDIKIT_YAKIN = 'sedikit_yakin';
    case CUKUP_YAKIN = 'cukup_yakin';
    case YAKIN = 'yakin';
    case SANGAT_YAKIN = 'sangat_yakin';

    public function label(): string
    {
        return match ($this) {
            self::TIDAK => 'Tidak',
            self::TIDAK_TAHU => 'Tidak tahu',
            self::SEDIKIT_YAKIN => 'Sedikit yakin',
            self::CUKUP_YAKIN => 'Cukup yakin',
            self::YAKIN => 'Yakin',
            self::SANGAT_YAKIN => 'Sangat yakin',
        };
    }

    public function nilai(): float
    {
        return match ($this) {
            self::TIDAK => 0.0,
            self::TIDAK_TAHU => 0.2,
            self::SEDIKIT_YAKIN => 0.4,
            self::CUKUP_YAKIN => 0.6,
            self::YAKIN => 0.8,
            self::SANGAT_YAKIN => 1.0,
        };
    }

    public function segmen(): int
    {
        return match ($this) {
            self::TIDAK => 0,
            self::TIDAK_TAHU => 1,
            self::SEDIKIT_YAKIN => 2,
            self::CUKUP_YAKIN => 3,
            self::YAKIN => 4,
            self::SANGAT_YAKIN => 5,
        };
    }

    public function penjelasan(): string
    {
        return match ($this) {
            self::TIDAK => 'Gejala ini tidak mendukung penyakit.',
            self::TIDAK_TAHU => 'Belum ada dasar untuk menilai hubungan gejala ini.',
            self::SEDIKIT_YAKIN => 'Gejala ini sedikit mendukung penyakit.',
            self::CUKUP_YAKIN => 'Gejala ini cukup kuat mendukung penyakit.',
            self::YAKIN => 'Gejala ini kuat mendukung penyakit.',
            self::SANGAT_YAKIN => 'Gejala ini sangat kuat, hampir selalu menandai penyakit.',
        };
    }

    public function nilaiString(): string
    {
        return number_format($this->nilai(), 3, '.', '');
    }

    public function nilaiFormatted(): string
    {
        return number_format($this->nilai(), 1, ',', '.');
    }

    public static function nilaiAllowlist(): array
    {
        return array_map(
            static fn (self $case): string => $case->nilaiString(),
            self::cases(),
        );
    }

    public static function normalize(mixed $value): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $number = (float) $value;
        $rounded = round($number, 3);

        if (abs($number - $rounded) >= 0.000000000001) {
            return (string) $value;
        }

        return number_format($rounded, 3, '.', '');
    }

    public static function fromNilai(float $nilai): ?self
    {
        foreach (self::cases() as $case) {
            if (abs($case->nilai() - $nilai) < 0.0001) {
                return $case;
            }
        }

        return null;
    }
}
