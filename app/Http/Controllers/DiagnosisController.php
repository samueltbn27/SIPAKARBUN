<?php

namespace App\Http\Controllers;

use App\Exceptions\KnowledgeApiException;
use App\Http\Requests\StoreDiagnosisRequest;
use App\Http\Resources\DiagnosisResource;
use App\Models\Diagnosis;
use App\Services\DiagnosisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Endpoint Diagnosis Mahasiswa 2 (tahap #7).
 *
 *   POST /api/diagnosis        — jalankan diagnosis baru.
 *   GET  /api/diagnosis        — histori diagnosis milik user yang login.
 *   GET  /api/diagnosis/{id}   — detail satu diagnosis milik user yang login.
 *
 * Semua endpoint butuh login (auth:sanctum). Histori & detail HANYA
 * menampilkan diagnosis milik user itu sendiri (authorization per-user).
 *
 * Alur POST (semua dalam SATU database transaction):
 *   request → validation (StoreDiagnosisRequest)
 *   → Knowledge API (KnowledgeService)
 *   → Forward Chaining → Certainty Factor → ranking
 *   → simpan diagnosis → simpan gejala → simpan hasil
 *   → return DiagnosisResource
 *
 * Transaction menjamin atomicity: kalau penyimpanan gagal di tengah
 * jalan, tidak ada sebagian data diagnosis yang tertinggal di DB.
 *
 * Tahap #12 (keamanan): semua kegagalan layer eksternal, database, dan
 * runtime dibungkus try/catch. Error tidak pernah bocor ke client;
 * user selalu mendapat response JSON yang wajar.
 */
class DiagnosisController extends Controller
{
    public function __construct(private readonly DiagnosisService $diagnosisService) {}

    public function store(StoreDiagnosisRequest $request)
    {
        $user = $request->user();

        try {
            $results = DB::transaction(
                fn () => $this->diagnosisService->diagnose(
                    commodityId: (int) $request->validated('commodity_id'),
                    symptomIds: $request->validated('symptom_ids'),
                    userId: $user?->id,
                    cfUser: $request->validated('symptom_confidence', []),
                )
            );
        } catch (KnowledgeApiException $e) {
            Log::error('Knowledge API gagal saat menjalankan diagnosis.', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Basis pengetahuan / referensi komoditas sedang tidak tersedia. Silakan coba lagi.',
            ], 502);
        } catch (Throwable $e) {
            Log::error('Kegagalan tidak terduga saat diagnosis.', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menjalankan diagnosis. Silakan coba lagi.',
            ], 500);
        }

        $first = $results->first();
        $diagnosisId = $first['diagnosis_id'] ?? null;

        if ($diagnosisId === null) {
            return response()->json([
                'message' => 'Tidak ada penyakit yang cocok dengan gejala yang dipilih.',
                'diagnosis_id' => null,
                'commodity' => null,
                'selected_symptoms' => [],
                'results' => [],
            ], 200);
        }

        $model = Diagnosis::with(['symptoms', 'results'])->findOrFail($diagnosisId);

        Log::info('Diagnosis selesai dijalankan.', [
            'user_id' => $user?->id,
            'diagnosis_id' => $diagnosisId,
        ]);

        return (new DiagnosisResource($model))->response($request)->setStatusCode(201);
    }

    public function index(Request $request)
    {
        $diagnoses = Diagnosis::query()
            ->untukUser($request->user()->id)
            ->with(['symptoms', 'results'])
            ->latest()
            ->paginate(max(1, min($request->integer('per_page', 15), 100)));

        return DiagnosisResource::collection($diagnoses);
    }

    public function show(Request $request, int $id)
    {
        // Authorization: user hanya boleh melihat diagnosis miliknya sendiri.
        $diagnosis = Diagnosis::query()
            ->untukUser($request->user()->id)
            ->with(['symptoms', 'results'])
            ->findOrFail($id);

        return new DiagnosisResource($diagnosis);
    }
}
