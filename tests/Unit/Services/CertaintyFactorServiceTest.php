<?php

namespace Tests\Unit\Services;

use App\Services\CertaintyFactorService;
use App\Services\CfResult;
use PHPUnit\Framework\TestCase;

/**
 * Test murni (tanpa database) untuk CertaintyFactorService (tahap #6).
 *
 * Memverifikasi:
 * - CF pakar (cf_pakar) tidak diubah oleh service,
 * - proses kombinasi CF berpasangan (Shortliffe-Buchanan),
 * - output CF hasil (final_cf) + percentage,
 * - hasil perhitungan dapat direproduksi (deterministik).
 *
 * Skenario sesuai requirement:
 *   1. satu gejala
 *   2. beberapa gejala
 *   3. beberapa rule untuk penyakit yang sama
 *   4. beberapa penyakit (nilai CF hasil tiap hipotesis dihitung mandiri)
 *   5. nilai CF 0
 *   6. nilai CF maksimum
 */
class CertaintyFactorServiceTest extends TestCase
{
    private CertaintyFactorService $service;

    protected function setUp(): void
    {
        $this->service = new CertaintyFactorService;
    }

    public function test_satu_gejala(): void
    {
        // Satu aturan cocok dengan cf_pakar 0.9 -> final_cf 0.9 (90%).
        $result = $this->service->calculate([0.9]);

        $this->assertInstanceOf(CfResult::class, $result);
        $this->assertSame(0.9, $result->final_cf);
        $this->assertSame(90.0, $result->percentage);
    }

    public function test_beberapa_gejala(): void
    {
        // Dua gejala cocok (cf 0.9 dan 0.7).
        // CF1 + CF2*(1-CF1) = 0.9 + 0.7*0.1 = 0.97 (97%).
        $result = $this->service->calculate([0.9, 0.7]);

        $this->assertSame(0.97, $result->final_cf);
        $this->assertSame(97.0, $result->percentage);
    }

    public function test_beberapa_rule_untuk_penyakit_yang_sama(): void
    {
        // Penyakit yang sama punya 3 rule aktif, semuanya cocok.
        // 0.9+0.5*0.1=0.95; 0.95+0.2*0.05=0.96 (96%).
        $result = $this->service->calculate([0.9, 0.5, 0.2]);

        $this->assertSame(0.96, $result->final_cf);
        $this->assertSame(96.0, $result->percentage);
    }

    public function test_beberapa_penyakit_dihitung_mandiri(): void
    {
        // Tiga hipotesis terpisah; kombinasi satu hipotesis tidak
        // memengaruhi hipotesis lain.
        $penyakitA = $this->service->calculate([0.9, 0.7]); // 0.97
        $penyakitB = $this->service->calculate([0.8]);      // 0.80
        $penyakitC = $this->service->calculate([0.6, 0.5]); // 0.60+0.5*0.4=0.80

        $this->assertSame(0.97, $penyakitA->final_cf);
        $this->assertSame(0.8, $penyakitB->final_cf);
        $this->assertSame(0.8, $penyakitC->final_cf);
        $this->assertNotSame($penyakitA, $penyakitB);
    }

    public function test_nilai_cf_nol(): void
    {
        // cf_pakar 0 tidak menambah keyakinan.
        // 0.9 + 0*(1-0.9) = 0.9 (90%).
        $result = $this->service->calculate([0.9, 0.0]);

        $this->assertSame(0.9, $result->final_cf);
        $this->assertSame(90.0, $result->percentage);
    }

    public function test_semua_cf_nol_hasil_nol_persen(): void
    {
        $result = $this->service->calculate([0.0, 0.0]);

        $this->assertSame(0.0, $result->final_cf);
        $this->assertSame(0.0, $result->percentage);
    }

    public function test_nilai_cf_maksimum(): void
    {
        // cf_pakar 1.0 -> final_cf 1.0 (100%).
        // Kombinasi dengan nilai apapun tetap tidak lebih dari 1.0:
        // 1.0 + 0.9*(1-1.0) = 1.0.
        $result = $this->service->calculate([1.0, 0.9]);

        $this->assertSame(1.0, $result->final_cf);
        $this->assertSame(100.0, $result->percentage);
    }

    public function test_daftar_cf_kosong_hasil_nol(): void
    {
        $result = $this->service->calculate([]);

        $this->assertSame(0.0, $result->final_cf);
        $this->assertSame(0.0, $result->percentage);
    }

    public function test_cf_pakar_tidak_diubah_oleh_service(): void
    {
        $cfPakar = [0.9, 0.7, 0.5];
        $before = $cfPakar;

        $this->service->calculate($cfPakar);

        $this->assertSame($before, $cfPakar);
    }

    public function test_hasil_dapat_direproduksi(): void
    {
        // Input yang sama -> output yang sama persis (deterministik).
        $a = $this->service->calculate([0.9, 0.7, 0.5]);
        $b = $this->service->calculate([0.9, 0.7, 0.5]);

        $this->assertSame($a->final_cf, $b->final_cf);
        $this->assertSame($a->percentage, $b->percentage);
    }

    public function test_cf_negatif_percentage_dibulatkan_nol(): void
    {
        // CF negatif = keyakinan menolak; percentage ditampilkan 0%.
        $result = $this->service->calculate([-0.5]);

        $this->assertSame(-0.5, $result->final_cf);
        $this->assertSame(0.0, $result->percentage);
    }

    public function test_kombinasi_campur_tanda(): void
    {
        // (0.9 + -0.6)/(1-min(0.9,0.6)) = 0.3/0.4 = 0.75 (75%).
        $result = $this->service->calculate([0.9, -0.6]);

        $this->assertSame(0.75, $result->final_cf);
        $this->assertSame(75.0, $result->percentage);
    }

    public function test_cf_gejala_adalah_perkalian_cf_user_dan_cf_pakar(): void
    {
        // CF_gejala = CF_user × CF_pakar (kontrak M2 §6).
        $this->assertSame(0.5, $this->service->cfGejala(1.0, 0.5));
        $this->assertSame(0.45, $this->service->cfGejala(0.5, 0.9));
        $this->assertSame(0.35, $this->service->cfGejala(0.5, 0.7));
        $this->assertSame(0.0, $this->service->cfGejala(0.0, 0.9));
    }

    public function test_kombinasi_setelah_cf_user_menerapkan_shortliffe(): void
    {
        // cf_user 0.5 atas gejala 1 (cf_pakar 0.9) & gejala 2 (cf_pakar 0.7):
        // CF_gejala = [0.45, 0.35]; 0.45 + 0.35*0.55 = 0.6425 -> 0.643.
        $cfGejala = [
            $this->service->cfGejala(0.5, 0.9),
            $this->service->cfGejala(0.5, 0.7),
        ];

        $result = $this->service->calculate($cfGejala);

        $this->assertSame(0.643, $result->final_cf);
        $this->assertSame(64.3, $result->percentage);
    }
}
