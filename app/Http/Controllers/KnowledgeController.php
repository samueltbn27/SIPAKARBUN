<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAturanCfRequest;
use App\Http\Requests\StoreGejalaRequest;
use App\Http\Requests\StorePenyakitRequest;
use App\Http\Requests\StoreSolusiRequest;
use App\Http\Requests\UpdateAturanCfRequest;
use App\Http\Requests\UpdateGejalaRequest;
use App\Http\Requests\UpdatePenyakitRequest;
use App\Http\Requests\UpdateSolusiRequest;
use App\Models\ActivityLog;
use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\KasusPenanganan;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\PermohonanPenanganan;
use App\Models\RefKomoditas;
use App\Models\Solusi;
use App\Services\KnowledgeImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function __construct(private readonly KnowledgeImageService $images) {}

    public function dashboard(): View
    {
        $stats = [
            'komoditas' => $this->hitungKomoditas(),
            'penyakit' => Penyakit::count(),
            'penyakit_aktif' => Penyakit::aktifSaja()->count(),
            'gejala' => Gejala::count(),
            'gejala_aktif' => Gejala::aktifSaja()->count(),
            'aturan_cf' => AturanCf::count(),
            'aturan_cf_aktif' => AturanCf::aktifSaja()->count(),
            'solusi' => Solusi::count(),
            'solusi_aktif' => Solusi::aktifSaja()->count(),
        ];

        // Knowledge Status — workflow draft yang sebenarnya (M1-FR-008):
        // draft / aktif / nonaktif dihitung langsung dari kolom status.
        $totalKnowledge = Penyakit::count() + Gejala::count() + AturanCf::count() + Solusi::count();
        $aktifCount = Penyakit::aktifSaja()->count() + Gejala::aktifSaja()->count() + AturanCf::aktifSaja()->count() + Solusi::aktifSaja()->count();
        $draftCount = Penyakit::draftSaja()->count() + Gejala::draftSaja()->count() + AturanCf::draftSaja()->count() + Solusi::draftSaja()->count();
        $nonaktifCount = $totalKnowledge - $aktifCount - $draftCount;

        $knowledgeStatus = [
            'total' => $totalKnowledge,
            'aktif' => $aktifCount,
            'draft' => $draftCount,
            'nonaktif' => $nonaktifCount,
            'aktif_pct' => $totalKnowledge > 0 ? round(($aktifCount / $totalKnowledge) * 100) : 0,
            'draft_pct' => $totalKnowledge > 0 ? round(($draftCount / $totalKnowledge) * 100) : 0,
            'nonaktif_pct' => $totalKnowledge > 0 ? round(($nonaktifCount / $totalKnowledge) * 100) : 0,
        ];

        $recentChanges = ActivityLog::whereIn('entity_type', ['Penyakit', 'Gejala', 'Aturan CF', 'Solusi'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        $cards = $this->knowledgeCards($stats);

        // Admin melihat dashboard berbeda
        if (auth()->user()?->hasRole('admin')) {
            $pendingUsers = \App\Models\User::where('is_active', false)
                ->with('roles')
                ->latest()
                ->limit(5)
                ->get();

            $recentUsers = \App\Models\User::with('roles')
                ->latest()
                ->limit(6)
                ->get();

            $userStats = [
                'total' => \App\Models\User::count(),
                'active' => \App\Models\User::where('is_active', true)->count(),
                'pending' => \App\Models\User::where('is_active', false)->count(),
            ];

            $roleBreakdown = [];
            foreach (['admin', 'popt', 'operator_uptd'] as $role) {
                $roleBreakdown[$role] = \App\Models\User::query()
                    ->whereHas('roles', fn ($query) => $query->where('name', $role))
                    ->count();
            }

            $adminLogs = ActivityLog::latest('created_at')->limit(8)->get();

            return view('knowledge.admin-dashboard', compact(
                'stats',
                'knowledgeStatus',
                'pendingUsers',
                'recentUsers',
                'userStats',
                'roleBreakdown',
                'adminLogs',
            ));
        }

        // OP (operator_uptd) melihat dashboard sendiri dengan template
        // visual yang sama dengan dashboard POPT — isi disesuaikan
        // tugas OP: pengajuan kasus & monitoring.
        if (auth()->user()?->hasRole('operator_uptd')) {
            $opStats = [
                'masuk' => PermohonanPenanganan::whereIn('status', [PermohonanPenanganan::STATUS_DIAJUKAN, PermohonanPenanganan::STATUS_SEDANG_DIREVIEW])->count(),
                'review' => PermohonanPenanganan::where('status', PermohonanPenanganan::STATUS_SEDANG_DIREVIEW)->count(),
                'diterima' => PermohonanPenanganan::where('status', PermohonanPenanganan::STATUS_DITERIMA)->count(),
                'ditolak' => PermohonanPenanganan::where('status', PermohonanPenanganan::STATUS_DITOLAK)->count(),
                'kasus' => KasusPenanganan::count(),
            ];

            return view('knowledge.op-dashboard', compact('stats', 'knowledgeStatus', 'opStats'));
        }

        return view('knowledge.dashboard', compact('stats', 'cards', 'recentChanges', 'knowledgeStatus'));
    }

    /**
     * Stable controller → view contract for the POPT knowledge dashboard.
     *
     * @param  array<string, int>  $stats
     * @return array<int, array<string, int|string>>
     */
    private function knowledgeCards(array $stats): array
    {
        return [
            ['label' => 'Komoditas', 'value' => $stats['komoditas'], 'meta' => 'Aktif', 'icon' => 'M12 3c-4 2-7 5-7 9a7 7 0 0 0 14 0c0-4-3-7-7-9Z', 'color' => '#e8f4ed', 'href' => route('knowledge.komoditas.index')],
            ['label' => 'Penyakit', 'value' => $stats['penyakit'], 'meta' => $stats['penyakit_aktif'].' Aktif', 'icon' => 'M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5l-8-3Z', 'color' => '#f2edf8', 'href' => route('knowledge.penyakit.index')],
            ['label' => 'Gejala', 'value' => $stats['gejala'], 'meta' => $stats['gejala_aktif'].' Aktif', 'icon' => 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4', 'color' => '#eef5e7', 'href' => route('knowledge.gejala.index')],
            ['label' => 'Aturan CF', 'value' => $stats['aturan_cf'], 'meta' => $stats['aturan_cf_aktif'].' Aktif', 'icon' => 'M4 18V6m0 12h16M8 15V9m4 6V5m4 10v-3', 'color' => '#fff4df', 'href' => route('knowledge.aturan-cf.index')],
            ['label' => 'Solusi', 'value' => $stats['solusi'], 'meta' => $stats['solusi_aktif'].' Aktif', 'icon' => 'M9 18h6m-5 3h4M12 3a6 6 0 0 0-3 11c.6.4 1 1.1 1 2h4c0-.9.4-1.6 1-2a6 6 0 0 0-3-11Z', 'color' => '#e7f3f4', 'href' => route('knowledge.solusi.index')],
        ];
    }

    public function komoditasIndex(Request $request): View
    {
        $komoditas = RefKomoditas::query()
            ->disbun()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(fn ($q2) => $q2
                    ->where('kode', 'like', "%{$request->q}%")
                    ->orWhere('nama', 'like', "%{$request->q}%"));
            })
            ->when($request->status === 'tersedia', fn ($q) => $q->tersedia())
            ->when($request->status === 'quarantined', fn ($q) => $q->quarantined())
            ->orderBy('nama')
            ->get();

        $totalKomoditas = RefKomoditas::disbun()->count();

        $komoditasDipakai = PenyakitKomoditas::selectRaw('komoditas_id, COUNT(*) as jumlah')
            ->groupBy('komoditas_id')
            ->pluck('jumlah', 'komoditas_id')
            ->toArray();

        return view('knowledge.komoditas.index', compact('komoditas', 'komoditasDipakai', 'totalKomoditas'));
    }

    private function hitungKomoditas(): int
    {
        try {
            return RefKomoditas::runtimeTersedia()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function penyakitIndex(Request $request): View
    {
        $query = Penyakit::query()
            ->with('penyakitKomoditas')
            ->when($request->boolean('aktif_saja'), fn($q) => $q->aktifSaja())
            ->when($request->q, function($q, $q2) {
                $q->where('kode', 'like', "%{$q2}%")
                  ->orWhere('nama', 'like', "%{$q2}%");
            })
            ->latest();

        $penyakit = $query->paginate(15)->withQueryString();

        return view('knowledge.penyakit.index', compact('penyakit'));
    }

    public function penyakitCreate(): View
    {
        $komoditas = RefKomoditas::runtimeTersedia()->orderBy('nama')->get(['id', 'kode', 'nama']);
        return view('knowledge.penyakit.create', compact('komoditas'));
    }

    public function penyakitStore(StorePenyakitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $komoditasIds = $data['komoditas_id'] ?? [];
        $image = $request->file('image');
        unset($data['komoditas_id']);
        unset($data['image']);

        // Knowledge baru default DRAFT — harus dipublikasikan lewat
        // halaman Publikasi sebelum dipakai diagnosis (M1-FR-008).
        $data['status'] = $data['status'] ?? Penyakit::STATUS_DRAFT;

        $penyakit = Penyakit::create($data);

        if ($image !== null) {
            $penyakit->update(['image_path' => $this->images->store($image, 'penyakit')]);
        }

        foreach (array_unique($komoditasIds) as $id) {
            PenyakitKomoditas::create([
                'penyakit_id' => $penyakit->id,
                'komoditas_id' => $id,
            ]);
        }

        ActivityLog::record('Penyakit', 'created', $penyakit->nama, $penyakit->id, "Menambahkan Penyakit \"{$penyakit->nama}\"");

        return redirect()->route('knowledge.penyakit.index')->with('success', 'Penyakit berhasil dibuat.');
    }

    public function penyakitEdit(Penyakit $penyakit): View
    {
        $komoditas = RefKomoditas::runtimeTersedia()->orderBy('nama')->get(['id', 'kode', 'nama']);
        $selectedKomoditas = $penyakit->penyakitKomoditas->pluck('komoditas_id')->toArray();
        return view('knowledge.penyakit.edit', compact('penyakit', 'komoditas', 'selectedKomoditas'));
    }

    public function penyakitUpdate(UpdatePenyakitRequest $request, Penyakit $penyakit): RedirectResponse
    {
        $data = $request->validated();
        $hasKomoditas = array_key_exists('komoditas_id', $data);
        $komoditasIds = $data['komoditas_id'] ?? [];
        $image = $request->file('image');
        unset($data['komoditas_id']);
        unset($data['image']);

        if ($image !== null) {
            $data['image_path'] = $this->images->replace($image, $penyakit->image_path, 'penyakit');
        }

        $penyakit->update($data);

        if ($hasKomoditas) {
            $penyakit->penyakitKomoditas()->delete();
            foreach (array_unique($komoditasIds) as $id) {
                PenyakitKomoditas::create([
                    'penyakit_id' => $penyakit->id,
                    'komoditas_id' => $id,
                ]);
            }
        }

        ActivityLog::record('Penyakit', 'updated', $penyakit->nama, $penyakit->id, "Mengubah Penyakit \"{$penyakit->nama}\"");

        return redirect()->route('knowledge.penyakit.index')->with('success', 'Penyakit berhasil diperbarui.');
    }

    public function penyakitDestroy(Penyakit $penyakit): RedirectResponse
    {
        $nama = $penyakit->nama;
        $id = $penyakit->id;
        $this->images->deleteIfLocal($penyakit->image_path);
        $penyakit->delete();

        ActivityLog::record('Penyakit', 'deleted', $nama, $id, "Menghapus Penyakit \"{$nama}\"");

        return redirect()->route('knowledge.penyakit.index')->with('success', 'Penyakit berhasil dihapus.');
    }

    public function gejalaIndex(Request $request): View
    {
        $query = Gejala::query()
            ->when($request->boolean('aktif_saja'), fn($q) => $q->aktifSaja())
            ->when($request->q, function($q, $q2) {
                $q->where('kode', 'like', "%{$q2}%")
                  ->orWhere('nama', 'like', "%{$q2}%");
            })
            ->latest();

        $gejala = $query->paginate(15)->withQueryString();

        return view('knowledge.gejala.index', compact('gejala'));
    }

    public function gejalaCreate(): View
    {
        return view('knowledge.gejala.create');
    }

    public function gejalaStore(StoreGejalaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $image = $request->file('image');
        unset($data['image']);
        $data['status'] = $data['status'] ?? Gejala::STATUS_DRAFT;

        $gejala = Gejala::create($data);

        if ($image !== null) {
            $gejala->update(['image_path' => $this->images->store($image, 'gejala')]);
        }

        ActivityLog::record('Gejala', 'created', $gejala->nama, $gejala->id, "Menambahkan Gejala \"{$gejala->nama}\"");

        return redirect()->route('knowledge.gejala.index')->with('success', 'Gejala berhasil dibuat.');
    }

    public function gejalaEdit(Gejala $gejala): View
    {
        return view('knowledge.gejala.edit', compact('gejala'));
    }

    public function gejalaUpdate(UpdateGejalaRequest $request, Gejala $gejala): RedirectResponse
    {
        $data = $request->validated();
        $image = $request->file('image');
        unset($data['image']);

        if ($image !== null) {
            $data['image_path'] = $this->images->replace($image, $gejala->image_path, 'gejala');
        }

        $gejala->update($data);

        ActivityLog::record('Gejala', 'updated', $gejala->nama, $gejala->id, "Mengubah Gejala \"{$gejala->nama}\"");

        return redirect()->route('knowledge.gejala.index')->with('success', 'Gejala berhasil diperbarui.');
    }

    public function gejalaDestroy(Gejala $gejala): RedirectResponse
    {
        $nama = $gejala->nama;
        $id = $gejala->id;
        $this->images->deleteIfLocal($gejala->image_path);
        $gejala->delete();

        ActivityLog::record('Gejala', 'deleted', $nama, $id, "Menghapus Gejala \"{$nama}\"");

        return redirect()->route('knowledge.gejala.index')->with('success', 'Gejala berhasil dihapus.');
    }

    public function aturanCfIndex(Request $request): View
    {
        $query = AturanCf::query()
            ->with(['penyakit', 'gejala'])
            ->when($request->boolean('aktif_saja'), fn($q) => $q->aktifSaja())
            ->when($request->penyakit_id, fn($q, $id) => $q->where('penyakit_id', $id))
            ->latest();

        $aturanCf = $query->paginate(15)->withQueryString();
        $penyakitList = Penyakit::orderBy('nama')->get(['id', 'nama']);

        return view('knowledge.aturan-cf.index', compact('aturanCf', 'penyakitList'));
    }

    public function aturanCfCreate(): View
    {
        $penyakitList = Penyakit::aktifSaja()->orderBy('nama')->get(['id', 'nama']);
        $gejalaList = Gejala::aktifSaja()->orderBy('nama')->pluck('nama', 'id');

        return view('knowledge.aturan-cf.create', compact('penyakitList', 'gejalaList'));
    }

    public function aturanCfStore(StoreAturanCfRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['status'] = $data['status'] ?? AturanCf::STATUS_DRAFT;

        $aturan = AturanCf::create($data);

        ActivityLog::record(
            'Aturan CF',
            'created',
            $aturan->penyakit?->nama . ' — ' . $aturan->gejala?->nama,
            $aturan->id,
            "Menambahkan aturan CF: Gejala \"{$aturan->gejala?->nama}\" pada Penyakit \"{$aturan->penyakit?->nama}\" (CF: {$aturan->cf_pakar})",
        );

        return redirect()->route('knowledge.aturan-cf.index')->with('success', 'Aturan CF berhasil dibuat.');
    }

    public function aturanCfEdit(AturanCf $aturanCf): View
    {
        $penyakitList = Penyakit::aktifSaja()->orderBy('nama')->get(['id', 'nama']);
        $gejalaList = Gejala::aktifSaja()->orderBy('nama')->pluck('nama', 'id');

        return view('knowledge.aturan-cf.edit', compact('aturanCf', 'penyakitList', 'gejalaList'));
    }

    public function aturanCfUpdate(UpdateAturanCfRequest $request, AturanCf $aturanCf): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $oldCf = $aturanCf->cf_pakar;
        $aturanCf->update($data);

        $desc = "Mengubah aturan CF: Gejala \"{$aturanCf->gejala?->nama}\" pada Penyakit \"{$aturanCf->penyakit?->nama}\"";
        if (isset($data['cf_pakar']) && (string) $oldCf !== (string) $data['cf_pakar']) {
            $desc = "Mengubah nilai CF Gejala \"{$aturanCf->gejala?->nama}\" pada Penyakit \"{$aturanCf->penyakit?->nama}\" dari {$oldCf} menjadi {$data['cf_pakar']}";
        }

        ActivityLog::record(
            'Aturan CF',
            'updated',
            $aturanCf->penyakit?->nama . ' — ' . $aturanCf->gejala?->nama,
            $aturanCf->id,
            $desc,
            isset($data['cf_pakar']) ? (string) $oldCf : null,
            isset($data['cf_pakar']) ? (string) $data['cf_pakar'] : null,
        );

        return redirect()->route('knowledge.aturan-cf.index')->with('success', 'Aturan CF berhasil diperbarui.');
    }

    public function aturanCfDestroy(AturanCf $aturanCf): RedirectResponse
    {
        if (!auth()->user()->hasAnyRole(['admin', 'operator_uptd'])) {
            return back()->with('error', 'Anda tidak memiliki hak menghapus aturan CF.');
        }

        $nama = $aturanCf->penyakit?->nama . ' — ' . $aturanCf->gejala?->nama;
        $id = $aturanCf->id;
        $aturanCf->delete();

        ActivityLog::record('Aturan CF', 'deleted', $nama, $id, "Menghapus aturan CF: Gejala \"{$aturanCf->gejala?->nama}\" pada Penyakit \"{$aturanCf->penyakit?->nama}\"");

        return redirect()->route('knowledge.aturan-cf.index')->with('success', 'Aturan CF berhasil dihapus.');
    }

    public function solusiIndex(Request $request): View
    {
        $query = Solusi::query()
            ->with('penyakit')
            ->when($request->boolean('aktif_saja'), fn($q) => $q->aktifSaja())
            ->when($request->penyakit_id, fn($q, $id) => $q->where('penyakit_id', $id))
            ->when($request->q, function($q, $q2) {
                $q->where('judul', 'like', "%{$q2}%");
            })
            ->latest();

        $solusi = $query->paginate(15)->withQueryString();
        $penyakitList = Penyakit::orderBy('nama')->get(['id', 'nama']);

        return view('knowledge.solusi.index', compact('solusi', 'penyakitList'));
    }

    public function solusiCreate(): View
    {
        $penyakitList = Penyakit::aktifSaja()->orderBy('nama')->get(['id', 'nama']);
        return view('knowledge.solusi.create', compact('penyakitList'));
    }

    public function solusiStore(StoreSolusiRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? Solusi::STATUS_DRAFT;

        $solusi = Solusi::create($data);

        ActivityLog::record('Solusi', 'created', $solusi->judul, $solusi->id, "Menambahkan Solusi \"{$solusi->judul}\"");

        return redirect()->route('knowledge.solusi.index')->with('success', 'Solusi berhasil dibuat.');
    }

    public function solusiEdit(Solusi $solusi): View
    {
        $penyakitList = Penyakit::aktifSaja()->orderBy('nama')->get(['id', 'nama']);
        return view('knowledge.solusi.edit', compact('solusi', 'penyakitList'));
    }

    public function solusiUpdate(UpdateSolusiRequest $request, Solusi $solusi): RedirectResponse
    {
        $solusi->update($request->validated());

        ActivityLog::record('Solusi', 'updated', $solusi->judul, $solusi->id, "Mengubah Solusi \"{$solusi->judul}\"");

        return redirect()->route('knowledge.solusi.index')->with('success', 'Solusi berhasil diperbarui.');
    }

    public function solusiDestroy(Solusi $solusi): RedirectResponse
    {
        $judul = $solusi->judul;
        $id = $solusi->id;
        $solusi->delete();

        ActivityLog::record('Solusi', 'deleted', $judul, $id, "Menghapus Solusi \"{$judul}\"");

        return redirect()->route('knowledge.solusi.index')->with('success', 'Solusi berhasil dihapus.');
    }

    public function publikasiIndex(): View
    {
        // Item yang menunggu tindakan publikasi: draft & nonaktif.
        // Draft -> Publish (aktif); Nonaktif -> Aktifkan kembali;
        // Aktif -> Nonaktifkan / kembalikan ke Draft (dari daftar aktif).
        $penyakitDraft = Penyakit::draftSaja()->withCount('aturanCf')->orderBy('nama')->get();
        $gejalaDraft = Gejala::draftSaja()->orderBy('nama')->get();
        $aturanCfDraft = AturanCf::draftSaja()->with(['penyakit', 'gejala'])->latest()->get();
        $solusiDraft = Solusi::draftSaja()->with('penyakit')->orderBy('judul')->get();

        $penyakitNonaktif = Penyakit::nonaktifSaja()->withCount('aturanCf')->orderBy('nama')->get();
        $gejalaNonaktif = Gejala::nonaktifSaja()->orderBy('nama')->get();
        $aturanCfNonaktif = AturanCf::nonaktifSaja()->with(['penyakit', 'gejala'])->latest()->get();
        $solusiNonaktif = Solusi::nonaktifSaja()->with('penyakit')->orderBy('judul')->get();

        $statistik = [
            'draft' => $penyakitDraft->count() + $gejalaDraft->count() + $aturanCfDraft->count() + $solusiDraft->count(),
            'aktif' => Penyakit::aktifSaja()->count() + Gejala::aktifSaja()->count() + AturanCf::aktifSaja()->count() + Solusi::aktifSaja()->count(),
            'nonaktif' => $penyakitNonaktif->count() + $gejalaNonaktif->count() + $aturanCfNonaktif->count() + $solusiNonaktif->count(),
        ];

        return view('knowledge.publikasi.index', compact(
            'penyakitDraft', 'gejalaDraft', 'aturanCfDraft', 'solusiDraft',
            'penyakitNonaktif', 'gejalaNonaktif', 'aturanCfNonaktif', 'solusiNonaktif',
            'statistik',
        ));
    }

    public function publikasiToggle(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'operator_uptd']), 403);

        $request->validate([
            'model' => ['required', 'in:Penyakit,Gejala,AturanCf,Solusi'],
            'id' => ['required', 'integer'],
            'status' => ['required', 'in:draft,aktif,nonaktif'],
        ]);

        $modelClass = 'App\\Models\\' . $request->model;
        $record = $modelClass::findOrFail($request->id);

        $entityName = $record->nama ?? $record->judul ?? '—';
        $record->update(['status' => $request->status]);

        $labelStatus = ['draft' => 'Draft', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
        $action = match ($request->status) {
            'aktif' => 'activated',
            'nonaktif' => 'deactivated',
            default => 'updated',
        };

        ActivityLog::record(
            $request->model,
            $action,
            $entityName,
            $record->id,
            "Mengubah status {$request->model} \"{$entityName}\" menjadi {$labelStatus[$request->status]}"
        );

        $kata = $request->status === 'aktif' ? 'dipublikasikan' : "diubah menjadi {$labelStatus[$request->status]}";
        return back()->with('success', "{$request->model} \"{$entityName}\" berhasil {$kata}.");
    }

    public function riwayatIndex(): View
    {
        $riwayat = ActivityLog::latest('created_at')->paginate(20);

        return view('knowledge.riwayat.index', compact('riwayat'));
    }

}
