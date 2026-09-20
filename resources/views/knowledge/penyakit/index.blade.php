@extends('layouts.app')

@section('title', 'Daftar Penyakit')
@section('topbar-title', 'Knowledge')
@section('topbar-subtitle', 'Basis pengetahuan dan referensi diagnosis')

@section('content')
@php
    $canManageKnowledge = auth()->user()?->hasAnyRole(['admin', 'operator_uptd']) ?? false;
    $isPopt = auth()->user()?->hasRole('popt') ?? false;
    $canCreateKnowledge = $canManageKnowledge || $isPopt;
    $canEditRecord = fn ($record) => $canManageKnowledge || ($isPopt && $record->status === 'draft');
    $createLabel = $isPopt ? 'Tambah Draft' : 'Tambah Penyakit';
    $hasFilters = request()->filled('q') || request()->boolean('aktif_saja');
@endphp

<div class="space-y-7">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="mb-2 text-xs font-bold uppercase tracking-[.14em] text-[#8a9990]">Basis Pengetahuan</p>
        </div>
        @if($canCreateKnowledge)
            <a href="{{ route('knowledge.penyakit.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-bold text-white shadow-[0_7px_16px_rgba(23,107,69,.16)] transition-colors hover:bg-[#115a39] focus-visible:outline-[#176b45]">{{ $createLabel }}</a>
        @endif
    </div>

    <section class="app-toolbar" aria-labelledby="filter-penyakit-heading">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 id="filter-penyakit-heading" class="app-toolbar__eyebrow">Filter daftar</h2>
                <p class="app-toolbar__meta mt-0.5">Cari berdasarkan kode atau nama penyakit.</p>
            </div>
            <span class="app-toolbar__meta">{{ $penyakit->total() }} data ditemukan</span>
        </div>
        <form method="GET" action="{{ route('knowledge.penyakit.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="min-w-0 flex-1">
                <label for="q" class="sr-only">Cari penyakit</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama penyakit..." class="app-toolbar__input px-3 py-2.5 text-sm">
            </div>
            <label class="inline-flex min-h-11 shrink-0 cursor-pointer select-none items-center gap-2 text-sm font-medium text-[#526159]">
                <input type="checkbox" name="aktif_saja" value="1" {{ request('aktif_saja') ? 'checked' : '' }} class="h-4 w-4 rounded border-[#c8d8cd] text-[#176b45] focus:ring-[#b8ddc3]">
                Aktif saja
            </label>
            <div class="app-toolbar__actions flex shrink-0 items-center gap-2">
                <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#115a39] sm:flex-none">Cari</button>
                @if($hasFilters)
                    <a href="{{ route('knowledge.penyakit.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-[#d4e0d7] px-3 py-2.5 text-sm font-bold text-[#526159] transition-colors hover:border-[#a8c9b2] hover:bg-[#f2f9f4] hover:text-[#176b45]">Reset</a>
                @endif
            </div>
        </form>
    </section>

    <section class="app-table-shell" aria-labelledby="data-penyakit-heading">
        <div class="flex flex-col gap-1 border-b border-[#edf2ee] px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 id="data-penyakit-heading" class="text-sm font-extrabold text-[#173b29]">Data penyakit</h2>
                <p class="mt-0.5 text-xs text-[#849189]">Status dan relasi komoditas dapat dipantau dari tabel.</p>
            </div>
            <span class="text-xs font-semibold text-[#849189]">{{ $penyakit->firstItem() ?? 0 }}–{{ $penyakit->lastItem() ?? 0 }} dari {{ $penyakit->total() }}</span>
        </div>

        <div class="app-table-scroll" tabindex="0" aria-label="Tabel daftar penyakit">
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Deskripsi</th>
                        <th scope="col">Status</th>
                        <th scope="col">Komoditas</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($penyakit as $p)
                        <tr>
                            <td class="whitespace-nowrap"><span class="app-table__code">{{ $p->kode ?: '-' }}</span></td>
                            <td><span class="app-table__primary">{{ $p->nama }}</span></td>
                            <td class="max-w-xs"><span class="line-clamp-2">{{ \Illuminate\Support\Str::limit($p->deskripsi, 100) }}</span></td>
                            <td class="whitespace-nowrap"><x-knowledge.status-badge :status="$p->status" /></td>
                            <td class="whitespace-nowrap">
                                @if ($p->penyakitKomoditas && $p->penyakitKomoditas->count() > 0)
                                    <span class="inline-flex items-center rounded-full bg-[#e8f4ed] px-2.5 py-1 text-xs font-bold text-[#176b45]">{{ $p->penyakitKomoditas->count() }} komoditas</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-[#f0f3f1] px-2.5 py-1 text-xs font-bold text-[#849189]">0 komoditas</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                @if($canEditRecord($p))
                                    <div class="inline-flex items-center gap-2" aria-label="Aksi untuk {{ $p->nama }}">
                                        <a href="{{ route('knowledge.penyakit.edit', $p) }}" class="app-table__action app-table__action--edit">Edit</a>
                                        @if($canManageKnowledge)
                                            <form method="POST" action="{{ route('knowledge.penyakit.destroy', $p) }}" data-confirm-title="Hapus penyakit?" data-confirm-message="Anda akan menghapus {{ $p->nama }}. Data yang dihapus tidak dapat dikembalikan." data-confirm-action="Hapus" data-confirm-tone="danger" class="inline">
                                                @method('DELETE')
                                                @csrf
                                                <button type="submit" class="app-table__action app-table__action--danger">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-[#849189]">Read-only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="app-table__empty px-4 py-14 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-[#b3c4b8]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm font-semibold">Belum ada data penyakit.</p>
                                    @if($canCreateKnowledge)<a href="{{ route('knowledge.penyakit.create') }}" class="text-sm font-bold text-[#176b45] hover:text-[#115a39]">{{ $createLabel }}</a>@endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="px-4 pb-3 pt-1 text-xs text-[#849189] sm:hidden">Geser tabel ke samping untuk melihat kolom aksi.</p>

        @if ($penyakit->hasPages())
            <div class="app-table__footer">
                {!! $penyakit->links() !!}
            </div>
        @endif
    </section>
</div>
@endsection
