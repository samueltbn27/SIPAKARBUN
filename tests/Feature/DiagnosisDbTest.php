<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Test skema & relasi database transaksi diagnosis (tahap #3).
 *
 * Fokus: memastikan migration berjalan benar, model & relasi bekerja,
 * snapshot tersimpan, dan cascade delete berperilaku sesuai desain.
 * Tidak menguji mesin hitung CF (tahap berikutnya).
 */
class DiagnosisDbTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnosis_bisa_dibuat_beserta_gejala_dan_hasilnya(): void
    {
        $user = User::factory()->create();

        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $user->id,
            'commodity_id' => 1,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        $diagnosis->symptoms()->create([
            'symptom_id' => 101,
            'symptom_name_snapshot' => 'Bercak jingga pada daun',
        ]);
        $diagnosis->symptoms()->create([
            'symptom_id' => 102,
            'symptom_name_snapshot' => 'Daun menggulung',
        ]);

        $diagnosis->results()->create([
            'disease_id' => 201,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'cf_value' => 0.875,
            'ranking' => 1,
        ]);

        $this->assertDatabaseHas('diagnoses', [
            'id' => $diagnosis->id,
            'user_id' => $user->id,
            'commodity_id' => 1,
            'status' => 'selesai',
        ]);
        $this->assertDatabaseCount('diagnosis_symptoms', 2);
        $this->assertDatabaseCount('diagnosis_results', 1);

        $this->assertSame($diagnosis->user->id, $user->id);
        $this->assertCount(2, $diagnosis->symptoms);
        $this->assertCount(1, $diagnosis->results);
        $this->assertSame('Karat Daun Kopi', $diagnosis->results->first()->disease_name_snapshot);
    }

    public function test_snapshot_nama_tetap_tersimpan_meski_kolom_id_menunjuk_knowledge_m1(): void
    {
        $diagnosis = Diagnosis::factory()->create();

        // sengaja tidak membuat data di tabel gejala/penyakit (miliki M1)
        $diagnosis->symptoms()->create([
            'symptom_id' => 999,
            'symptom_name_snapshot' => 'Gejala yang sudah tidak ada',
        ]);
        $diagnosis->results()->create([
            'disease_id' => 888,
            'disease_name_snapshot' => 'Penyakit lama',
            'cf_value' => 0.5,
            'ranking' => 1,
        ]);

        $this->assertDatabaseHas('diagnosis_symptoms', [
            'diagnosis_id' => $diagnosis->id,
            'symptom_id' => 999,
            'symptom_name_snapshot' => 'Gejala yang sudah tidak ada',
        ]);
        $this->assertDatabaseHas('diagnosis_results', [
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 888,
            'disease_name_snapshot' => 'Penyakit lama',
        ]);
    }

    public function test_menghapus_diagnosis_menghapus_gejala_dan_hasil_terkait(): void
    {
        $diagnosis = Diagnosis::factory()->create();

        $diagnosis->symptoms()->create(['symptom_id' => 1, 'symptom_name_snapshot' => 'A']);
        $diagnosis->symptoms()->create(['symptom_id' => 2, 'symptom_name_snapshot' => 'B']);
        $diagnosis->results()->create([
            'disease_id' => 1, 'disease_name_snapshot' => 'X', 'cf_value' => 0.9, 'ranking' => 1,
        ]);

        $diagnosis->delete();

        $this->assertDatabaseMissing('diagnoses', ['id' => $diagnosis->id]);
        $this->assertDatabaseMissing('diagnosis_symptoms', ['diagnosis_id' => $diagnosis->id]);
        $this->assertDatabaseMissing('diagnosis_results', ['diagnosis_id' => $diagnosis->id]);
    }

    public function test_gejala_duplikat_dalam_satu_diagnosis_ditolak_database(): void
    {
        $diagnosis = Diagnosis::factory()->create();
        $diagnosis->symptoms()->create(['symptom_id' => 1, 'symptom_name_snapshot' => 'A']);

        $this->expectException(QueryException::class);
        $diagnosis->symptoms()->create(['symptom_id' => 1, 'symptom_name_snapshot' => 'A lagi']);
    }

    public function test_penyakit_duplikat_dalam_satu_diagnosis_ditolak_database(): void
    {
        $diagnosis = Diagnosis::factory()->create();
        $diagnosis->results()->create([
            'disease_id' => 1, 'disease_name_snapshot' => 'X', 'cf_value' => 0.9, 'ranking' => 1,
        ]);

        $this->expectException(QueryException::class);
        $diagnosis->results()->create([
            'disease_id' => 1, 'disease_name_snapshot' => 'X', 'cf_value' => 0.8, 'ranking' => 2,
        ]);
    }

    public function test_relasi_hasil_terurut_berdasarkan_ranking(): void
    {
        $diagnosis = Diagnosis::factory()->create();

        $diagnosis->results()->create([
            'disease_id' => 1, 'disease_name_snapshot' => 'Rendah', 'cf_value' => 0.3, 'ranking' => 2,
        ]);
        $diagnosis->results()->create([
            'disease_id' => 2, 'disease_name_snapshot' => 'Tertinggi', 'cf_value' => 0.95, 'ranking' => 1,
        ]);

        $names = $diagnosis->results->pluck('disease_name_snapshot')->all();

        $this->assertSame(['Tertinggi', 'Rendah'], $names);
    }

    public function test_scope_untuk_user_memfilter_diagnosis(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Diagnosis::factory()->count(2)->create(['user_id' => $userA->id]);
        Diagnosis::factory()->create(['user_id' => $userB->id]);

        $this->assertCount(2, Diagnosis::untukUser($userA->id)->get());
        $this->assertCount(1, Diagnosis::untukUser($userB->id)->get());
    }

    public function test_tabel_yang_dimiliki_m1_tidak_dibuat_ulang(): void
    {
        $this->assertFalse(Schema::hasTable('diseases'));
        $this->assertFalse(Schema::hasTable('symptoms'));
        $this->assertFalse(Schema::hasTable('rules'));
        $this->assertFalse(Schema::hasTable('rule_details'));
        $this->assertFalse(Schema::hasTable('solutions'));
        $this->assertFalse(Schema::hasTable('commodities'));
    }

    public function test_model_snapshot_tidak_memakai_updated_at(): void
    {
        $diagnosis = Diagnosis::factory()->create();

        $symptom = $diagnosis->symptoms()->create(['symptom_id' => 1, 'symptom_name_snapshot' => 'A']);
        $result = $diagnosis->results()->create([
            'disease_id' => 1, 'disease_name_snapshot' => 'X', 'cf_value' => 0.9, 'ranking' => 1,
        ]);

        $this->assertNotNull($symptom->created_at);
        $this->assertNotNull($result->created_at);
    }
}
