@extends('layouts.app')

@section('title', 'Publikasi Knowledge')
@section('subtitle', 'Kelola workflow draft → aktif → nonaktif')

@section('content')
@php
    $canManageKnowledge = auth()->user()?->hasAnyRole(['admin', 'operator_uptd']) ?? false;
    // Petakan tiap entity ke baris seragam: id, nama, sub.
    $mapPenyakit = fn ($item) => ['id' => $item->id, 'nama' => $item->nama, 'sub' => $item->kode ? "Kode {$item->kode}" : null];
    $mapGejala = fn ($item) => ['id' => $item->id, 'nama' => $item->nama, 'sub' => $item->kode ? "Kode {$item->kode}" : null];
    $mapAturan = fn ($item) => ['id' => $item->id, 'nama' => ($item->penyakit?->nama ?? '-') . ' — ' . ($item->gejala?->nama ?? '-'), 'sub' => 'CF ' . number_format((float) $item->cf_pakar, 3)];
    $mapSolusi = fn ($item) => ['id' => $item->id, 'nama' => $item->judul, 'sub' => $item->penyakit?->nama];

    $entityDefs = [
        ['model' => 'Penyakit', 'label' => 'Penyakit'],
        ['model' => 'Gejala', 'label' => 'Gejala'],
        ['model' => 'AturanCf', 'label' => 'Aturan CF'],
        ['model' => 'Solusi', 'label' => 'Solusi'],
    ];

    $draftSets = [
        $penyakitDraft->map($mapPenyakit), $gejalaDraft->map($mapGejala),
        $aturanCfDraft->map($mapAturan), $solusiDraft->map($mapSolusi),
    ];
    $nonaktifSets = [
        $penyakitNonaktif->map($mapPenyakit), $gejalaNonaktif->map($mapGejala),
        $aturanCfNonaktif->map($mapAturan), $solusiNonaktif->map($mapSolusi),
    ];
    $totalDraft = array_sum(array_map(fn ($s) => $s->count(), $draftSets));
    $totalNonaktif = array_sum(array_map(fn ($s) => $s->count(), $nonaktifSets));
    $aktifCounts = [
        'Penyakit' => [\App\Models\Penyakit::aktifSaja()->count(), 'knowledge.penyakit.index'],
        'Gejala' => [\App\Models\Gejala::aktifSaja()->count(), 'knowledge.gejala.index'],
        'AturanCf' => [\App\Models\AturanCf::aktifSaja()->count(), 'knowledge.aturan-cf.index'],
        'Solusi' => [\App\Models\Solusi::aktifSaja()->count(), 'knowledge.solusi.index'],
    ];
@endphp
<div class="max-w-[1500px] mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Knowledge</span><span>/</span><span class="text-[#176b45]">Publikasi</span></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Publikasi Knowledge</h1>
        <p class="mt-1 text-sm text-[#77847c]">Kelola workflow knowledge: <strong class="text-[#b8860b]">Draft</strong> → <strong class="text-[#176b45]">Aktif</strong> → <strong class="text-[#8b9790]">Nonaktif</strong>.</p>
    </div>

    {{-- Statistik ringkas --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 text-center">
            <div class="text-2xl font-bold text-[#b8860b]">{{ $statistik['draft'] }}</div>
            <div class="text-xs font-medium text-[#748179] mt-1">Draft menunggu publish</div>
        </div>
        <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 text-center">
            <div class="text-2xl font-bold text-[#176b45]">{{ $statistik['aktif'] }}</div>
            <div class="text-xs font-medium text-[#748179] mt-1">Aktif (dipakai diagnosis)</div>
        </div>
        <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 text-center">
            <div class="text-2xl font-bold text-[#8b9790]">{{ $statistik['nonaktif'] }}</div>
            <div class="text-xs font-medium text-[#748179] mt-1">Nonaktif</div>
        </div>
    </div>

    <div class="rounded-xl bg-[#eef6f1] border border-[#d6ebe0] p-4 text-sm text-[#2d6b4a] flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-[#176b45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Knowledge berstatus <strong>Draft</strong> atau <strong>Nonaktif</strong> <strong>tidak dapat dikonsumsi modul Diagnosis</strong> (PRD M1-FR-009). Publikasikan untuk mengaktifkan.</span>
    </div>

    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white overflow-hidden" x-data="{ tab: 'draft' }">
        <div class="border-b border-[#e8efea] flex">
            <button @click="tab = 'draft'" :class="tab === 'draft' ? 'border-[#176b45] text-[#176b45]' : 'border-transparent text-[#8b9790] hover:text-[#526159]'" class="px-5 py-3.5 text-sm font-semibold border-b-2">
                Draft
                <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $totalDraft > 0 ? 'bg-[#fff4df] text-[#b8860b]' : 'bg-[#f0f4f1] text-[#9aa59e]' }}">{{ $totalDraft }}</span>
            </button>
            <button @click="tab = 'nonaktif'" :class="tab === 'nonaktif' ? 'border-[#176b45] text-[#176b45]' : 'border-transparent text-[#8b9790] hover:text-[#526159]'" class="px-5 py-3.5 text-sm font-semibold border-b-2">
                Nonaktif
                <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $totalNonaktif > 0 ? 'bg-[#f0f4f1] text-[#8b9790]' : 'bg-[#f0f4f1] text-[#9aa59e]' }}">{{ $totalNonaktif }}</span>
            </button>
        </div>

        {{-- TAB DRAFT --}}
        <div x-show="tab === 'draft'" class="p-5 space-y-5">
            @foreach ($entityDefs as $def)
                @php($rows = $draftSets[$loop->index])
                <div>
                    <h3 class="text-xs font-bold text-[#8b9790] uppercase tracking-wider mb-2">{{ $def['label'] }}</h3>
                    @if ($rows->isNotEmpty())
                    <div class="rounded-lg border border-[#eef3ef] divide-y divide-[#f0f4f1] overflow-hidden">
                        @foreach ($rows as $row)
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-[#f7faf8] transition">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-[#173b29] truncate">{{ $row['nama'] }}</div>
                                @if ($row['sub'])<div class="text-xs text-[#9aa59e]">{{ $row['sub'] }}</div>@endif
                            </div>
                            @if($canManageKnowledge)<form method="POST" action="{{ route('knowledge.publikasi.toggle') }}" class="flex-shrink-0">
                                @csrf
                                <input type="hidden" name="model" value="{{ $def['model'] }}">
                                <input type="hidden" name="id" value="{{ $row['id'] }}">
                                <input type="hidden" name="status" value="aktif">
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#176b45] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#115a39] transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Publish
                                </button>
                            </form>@else<span class="text-xs text-[#8b9790]">Read-only</span>@endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-[#b0bab3] px-1">Tidak ada draft {{ strtolower($def['label']) }}.</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- TAB NONAKTIF --}}
        <div x-show="tab === 'nonaktif'" x-cloak style="display:none;" class="p-5 space-y-5">
            @foreach ($entityDefs as $def)
                @php($rows = $nonaktifSets[$loop->index])
                <div>
                    <h3 class="text-xs font-bold text-[#8b9790] uppercase tracking-wider mb-2">{{ $def['label'] }}</h3>
                    @if ($rows->isNotEmpty())
                    <div class="rounded-lg border border-[#eef3ef] divide-y divide-[#f0f4f1] overflow-hidden">
                        @foreach ($rows as $row)
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-[#f7faf8] transition">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-[#173b29] truncate">{{ $row['nama'] }}</div>
                                @if ($row['sub'])<div class="text-xs text-[#9aa59e]">{{ $row['sub'] }}</div>@endif
                            </div>
                            @if($canManageKnowledge)<form method="POST" action="{{ route('knowledge.publikasi.toggle') }}" class="flex-shrink-0">
                                @csrf
                                <input type="hidden" name="model" value="{{ $def['model'] }}">
                                <input type="hidden" name="id" value="{{ $row['id'] }}">
                                <input type="hidden" name="status" value="aktif">
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-[#d6e0d9] bg-white px-3 py-1.5 text-xs font-semibold text-[#176b45] hover:bg-[#f3f8f4] transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Aktifkan Kembali
                                </button>
                            </form>@else<span class="text-xs text-[#8b9790]">Read-only</span>@endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-[#b0bab3] px-1">Tidak ada {{ strtolower($def['label']) }} nonaktif.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Aktifkan penonaktifan cepat: daftar knowledge aktif --}}
    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
        <h2 class="text-base font-bold text-[#173b29]">Knowledge Aktif</h2>
        <p class="text-xs text-[#89968e] mt-1 mb-4">Nonaktifkan jika tidak lagi layak dipakai diagnosis.</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach ($aktifCounts as $label => [$count, $routeName])
            <a href="{{ route($routeName) }}" class="rounded-xl border border-[#e8f4ed] bg-[#f7fcf9] p-4 hover:border-[#176b45]/40 transition">
                <div class="text-xl font-bold text-[#176b45]">{{ $count }}</div>
                <div class="text-xs font-medium text-[#748179] mt-0.5">{{ $label }} Aktif</div>
            </a>
            @endforeach
        </div>
        <p class="text-xs text-[#b0bab3] mt-4">Untuk menonaktifkan atau mengembalikan ke draft, gunakan tombol Edit pada masing-masing daftar.</p>
    </div>
</div>
@endsection
