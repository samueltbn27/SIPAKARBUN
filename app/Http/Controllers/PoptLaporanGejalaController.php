<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewLaporanGejalaRequest;
use App\Models\ActivityLog;
use App\Models\Gejala;
use App\Models\LaporanGejala;
use App\Services\LaporanGejalaImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PoptLaporanGejalaController extends Controller
{
    public function index(Request $request): View
    {
        $allowedStatuses = [
            LaporanGejala::STATUS_DIAJUKAN,
            LaporanGejala::STATUS_PERLU_INFORMASI,
            LaporanGejala::STATUS_DUPLIKAT,
            LaporanGejala::STATUS_DRAFT_DIBUAT,
        ];
        $status = $request->query('status');
        if (! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $laporan = LaporanGejala::query()
            ->with('reporter')
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('popt.laporan-gejala.index', compact('laporan', 'status', 'allowedStatuses'));
    }

    public function show(LaporanGejala $laporanGejala): View
    {
        $laporanGejala->load(['reporter', 'reviewer', 'gejala']);

        return view('popt.laporan-gejala.show', ['laporan' => $laporanGejala]);
    }

    public function review(
        ReviewLaporanGejalaRequest $request,
        LaporanGejala $laporanGejala,
        LaporanGejalaImageService $images,
    ): RedirectResponse {
        abort_unless($laporanGejala->status === LaporanGejala::STATUS_DIAJUKAN, 409);

        $data = $request->validated();
        $action = $data['action'];
        $imageCopy = null;

        try {
            DB::transaction(function () use ($data, $action, $request, $laporanGejala, $images, &$imageCopy): void {
                $updates = [
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'review_note' => $data['review_note'] ?? null,
                ];

                if ($action === LaporanGejala::STATUS_PERLU_INFORMASI) {
                    $updates['status'] = LaporanGejala::STATUS_PERLU_INFORMASI;
                    $laporanGejala->update($updates);
                } elseif ($action === LaporanGejala::STATUS_DUPLIKAT) {
                    $updates['status'] = LaporanGejala::STATUS_DUPLIKAT;
                    $laporanGejala->update($updates);
                } else {
                    $imageCopy = $images->copyToKnowledge($laporanGejala->image_path);
                    if ($laporanGejala->image_path !== null && $imageCopy === null) {
                        throw new RuntimeException('Foto laporan tidak dapat disalin ke draft gejala.');
                    }

                    $draft = Gejala::create([
                        'nama' => $data['draft_symptom_name'],
                        'deskripsi' => $laporanGejala->description,
                        'image_path' => $imageCopy,
                        'status' => Gejala::STATUS_DRAFT,
                    ]);

                    $updates['status'] = LaporanGejala::STATUS_DRAFT_DIBUAT;
                    $updates['gejala_id'] = $draft->id;
                    $laporanGejala->update($updates);

                    ActivityLog::record(
                        'Gejala',
                        'created',
                        $draft->nama,
                        $draft->id,
                        "Membuat draft Gejala dari laporan {$laporanGejala->report_code}.",
                    );
                }

                ActivityLog::record(
                    'Laporan Gejala',
                    'reviewed',
                    $laporanGejala->report_code,
                    $laporanGejala->id,
                    "POPT meninjau laporan gejala dengan status {$updates['status']}.",
                );
            });
        } catch (Throwable $exception) {
            if ($imageCopy !== null) {
                Storage::disk('public')->delete($imageCopy);
            }

            throw $exception;
        }

        $message = match ($action) {
            LaporanGejala::STATUS_PERLU_INFORMASI => 'Permintaan informasi tambahan dikirim ke Poktan.',
            LaporanGejala::STATUS_DUPLIKAT => 'Laporan ditandai sebagai duplikat.',
            default => 'Draft gejala berhasil dibuat. Draft menunggu publikasi Admin atau Operator UPTD.',
        };

        return to_route('popt.laporan-gejala.show', $laporanGejala)->with('success', $message);
    }
}
