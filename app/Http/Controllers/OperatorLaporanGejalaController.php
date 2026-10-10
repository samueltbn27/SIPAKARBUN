<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewLaporanOperatorRequest;
use App\Models\ActivityLog;
use App\Models\LaporanGejala;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Review Operator atas kajian POPT — gate alur gejala baru (Poin 5).
 *
 *   Gejala Baru → Kajian POPT → [Review Operator] → Relasi penyakit & CF
 *   → Publish → Dipakai Diagnosis berikutnya.
 *
 * Hanya laporan berstatus `draft_dibuat` yang BELUM direview yang bisa
 * diputus. Persetujuan (`setuju`) membuka gate pembuatan relasi CF untuk
 * gejala asal laporan; penolakan (`ditolak` + catatan wajib) menghentikan
 * rantai (gejala tidak bisa direlasikan maupun dipublish).
 */
class OperatorLaporanGejalaController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'menunggu');
        if (! in_array($filter, ['menunggu', 'disetujui', 'ditolak', 'semua'], true)) {
            $filter = 'menunggu';
        }

        $laporan = LaporanGejala::query()
            ->with(['reporter', 'reviewer', 'gejala', 'operatorReviewer'])
            ->where('status', LaporanGejala::STATUS_DRAFT_DIBUAT)
            ->when($filter === 'menunggu', fn ($q) => $q->whereNull('operator_review'))
            ->when($filter === 'disetujui', fn ($q) => $q->where('operator_review', LaporanGejala::REVIEW_SETUJU))
            ->when($filter === 'ditolak', fn ($q) => $q->where('operator_review', LaporanGejala::REVIEW_DITOLAK))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $menungguCount = LaporanGejala::query()
            ->where('status', LaporanGejala::STATUS_DRAFT_DIBUAT)
            ->whereNull('operator_review')
            ->count();

        return view('operator.laporan-gejala.index', compact('laporan', 'filter', 'menungguCount'));
    }

    public function show(LaporanGejala $laporanGejala): View
    {
        abort_unless($laporanGejala->status === LaporanGejala::STATUS_DRAFT_DIBUAT, 404);

        $laporanGejala->load(['reporter', 'reviewer', 'gejala', 'operatorReviewer', 'diagnosis']);

        return view('operator.laporan-gejala.show', ['laporan' => $laporanGejala]);
    }

    public function review(ReviewLaporanOperatorRequest $request, LaporanGejala $laporanGejala): RedirectResponse
    {
        abort_unless($laporanGejala->status === LaporanGejala::STATUS_DRAFT_DIBUAT, 409);
        abort_if($laporanGejala->operator_review !== null, 409, 'Kajian ini sudah direview Operator.');

        $data = $request->validated();

        $laporanGejala->update([
            'operator_review' => $data['keputusan'],
            'operator_review_note' => $data['catatan'] ?? null,
            'operator_reviewed_by' => $request->user()->id,
            'operator_reviewed_at' => now(),
        ]);

        ActivityLog::record(
            'Laporan Gejala',
            'operator_reviewed',
            $laporanGejala->report_code,
            $laporanGejala->id,
            $data['keputusan'] === LaporanGejala::REVIEW_SETUJU
                ? "Operator menyetujui kajian POPT atas laporan {$laporanGejala->report_code}; relasi CF boleh dibuat."
                : "Operator menolak kajian POPT atas laporan {$laporanGejala->report_code}.",
        );

        $message = $data['keputusan'] === LaporanGejala::REVIEW_SETUJU
            ? 'Kajian disetujui. Relasi penyakit & CF kini dapat dibuat untuk draft gejala ini.'
            : 'Kajian ditolak. Draft gejala ini tidak dapat direlasikan maupun dipublish.';

        return to_route('operator.laporan-gejala.show', $laporanGejala)->with('success', $message);
    }
}
