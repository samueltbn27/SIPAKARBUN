@extends('layouts.app')

@section('title', 'Daftar Penyakit')

@section('content')
@php
    $canManageKnowledge = auth()->user()?->hasAnyRole(['admin', 'operator_uptd']) ?? false;
    $isPopt = auth()->user()?->hasRole('popt') ?? false;
    $canCreateKnowledge = $canManageKnowledge || $isPopt;
    $canEditRecord = fn ($record) => $canManageKnowledge || ($isPopt && $record->status === 'draft');
    $createLabel = $isPopt ? 'Tambah Draft' : 'Tambah Penyakit';
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="mb-2 text-xs font-bold uppercase tracking-[.14em] text-[#8a9990]">Basis Pengetahuan</p>
            <h1 class="break-words text-xl font-extrabold tracking-tight text-[#173b29] sm:text-2xl">Daftar Penyakit</h1>
            <p class="mt-1 text-sm leading-6 text-[#66746c]">Kelola data penyakit untuk basis pengetahuan.</p>
        </div>
        @if($canCreateKnowledge)<a href="{{ route('knowledge.penyakit.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#115a39]">{{ $createLabel }}</a>@endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('knowledge.penyakit.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama penyakit..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="aktif_saja" id="aktif_saja" value="1" {{ request('aktif_saja') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <label for="aktif_saja" class="text-sm text-gray-700">Aktif saja</label>
            </div>
            <button type="submit" class="bg-green-600 text-white hover:bg-green-700 rounded-lg px-4 py-2 text-sm font-medium">
                Cari
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel daftar penyakit">
        <table class="min-w-[860px] w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Deskripsi</th>
                    <th class="px-4 py-3">Komoditas</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Gejala</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($penyakit as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-700">{{ $p->kode }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $p->nama }}</td>
                    <td class="px-4 py-3 max-w-[220px] text-gray-600">
                        {{ \Illuminate\Support\Str::limit($p->deskripsi, 60) }}
                    </td>
                    <td class="px-4 py-3">
                        @php $komoditasList = $p->penyakitKomoditas->filter(fn ($pk) => $pk->komoditas !== null); @endphp
                        @if ($komoditasList->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach ($komoditasList->take(2) as $pk)
                                    <span class="inline-flex items-center rounded-md border border-[#d6e9dd] bg-[#f0f8f3] px-2 py-0.5 text-xs font-semibold text-[#176b45]">{{ \Illuminate\Support\Str::limit($pk->komoditas->nama, 16) }}</span>
                                @endforeach
                                @if ($komoditasList->count() > 2)
                                    <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-500" title="{{ $komoditasList->count() }} komoditas terkait">+{{ $komoditasList->count() - 2 }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-knowledge.status-badge :status="$p->status" />
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center rounded-full {{ $p->aturan_cf_count > 0 ? 'bg-[#e8f4ed] text-[#176b45]' : 'bg-gray-100 text-gray-400' }} px-2.5 py-0.5 text-xs font-semibold">{{ $p->aturan_cf_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($canEditRecord($p))<div class="inline-flex items-center gap-2">
                            <a href="{{ route('knowledge.penyakit.edit', $p) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                Edit
                            </a>
                            @if($canManageKnowledge)<form method="POST" action="{{ route('knowledge.penyakit.destroy', $p) }}" data-confirm-title="Hapus penyakit?" data-confirm-message="Data yang dihapus tidak dapat dikembalikan." data-confirm-action="Hapus" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white hover:bg-red-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                                        Hapus
                                    </button>
                                </form>@endif
                        </div>@else<span class="text-xs text-gray-500">Read-only</span>@endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m-6 8h6a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2z" />
                            </svg>
                            <p>Belum ada data penyakit.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <p class="px-4 py-3 text-xs text-[#66746c] sm:hidden">Geser tabel ke samping untuk melihat seluruh kolom dan aksi.</p>
    </div>

    <div class="flex justify-center">
        {!! $penyakit->links() !!}
    </div>
</div>
@endsection
