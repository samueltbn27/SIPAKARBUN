@extends('layouts.app')

@section('title', 'Permohonan Penanganan')

@php
    $statusLabels = ['diajukan' => 'Diajukan', 'sedang_direview' => 'Sedang Direview', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak'];
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-2xl font-bold text-[#173b29]">Permohonan Penanganan</h1><p class="mt-1 text-sm text-[#77847c]">Review, terima, atau tolak pengajuan Poktan.</p></div>
        <a href="{{ route('operator.kasus.index') }}" class="rounded-lg border border-[#cfe2d5] px-4 py-2 text-sm font-semibold text-[#176b45]">Monitoring Kasus</a>
    </div>

    <form method="GET" class="flex flex-col gap-3 rounded-xl border border-[#e6eee8] bg-white p-4 sm:flex-row sm:items-end">
        <div><label for="status" class="block text-xs font-semibold text-gray-600">Status</label><select id="status" name="status" class="mt-1 rounded-lg border-gray-300 text-sm"><option value="">Semua</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="rounded-lg bg-[#176b45] px-4 py-2 text-sm font-semibold text-white">Terapkan</button>
        <a href="{{ route('operator.permohonan') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm text-gray-700">Reset</a>
    </form>

    <div class="overflow-hidden rounded-xl border border-[#e6eee8] bg-white">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Kode</th><th class="px-4 py-3 text-left">Pemohon / Kelompok Tani</th><th class="px-4 py-3 text-left">Lokasi</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($permohonan as $item)
                <tr><td class="px-4 py-3 font-mono text-xs">{{ $item->permohonan_code }}</td><td class="px-4 py-3"><div class="font-semibold text-gray-900">{{ $item->creator?->name ?? '-' }}</div><div class="text-xs text-gray-500">{{ $item->kelompok_tani_name_snapshot }}</div></td><td class="px-4 py-3 text-gray-600">{{ $item->kabupaten ?: ($item->alamat_kasus ?: '-') }}</td><td class="px-4 py-3"><span class="rounded-full bg-[#eef6f1] px-2.5 py-1 text-xs font-semibold text-[#176b45]">{{ $statusLabels[$item->status] ?? $item->status }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('operator.permohonan.show', $item->id) }}" class="font-semibold text-[#176b45]">Detail</a></td></tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">Belum ada permohonan pada filter ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $permohonan->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
