@extends('layouts.app')

@section('title', 'Daftar Komoditas')
@section('subtitle', 'Kelola data komoditas referensi perkebunan')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Master Data</span><span>/</span><span class="text-[#176b45]">Komoditas</span></div>
            <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Daftar Komoditas</h1>
            <p class="mt-1 text-sm text-[#77847c]">Komoditas referensi yang menjadi cakupan basis pengetahuan SIPAKARBUN.</p>
        </div>
    </div>

    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
        <div class="flex items-start gap-3 rounded-lg bg-[#eef6f1] border border-[#d6ebe0] p-4 text-sm text-[#2d6b4a] mb-5">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-[#176b45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Data komoditas berasal dari Shared Integration (PRD §23.4). Saat ini menggunakan data MOCK.</span>
        </div>

        @if($komoditas->isNotEmpty())
        <div class="overflow-x-auto -mx-6 sm:-mx-7">
            <table class="min-w-full divide-y divide-[#eef3ef]">
                <thead>
                    <tr class="bg-[#f7faf8]">
                        <th class="px-6 sm:px-7 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Kode</th>
                        <th class="px-6 sm:px-7 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Nama Komoditas</th>
                        <th class="px-6 sm:px-7 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Nama Latin</th>
                        <th class="px-6 sm:px-7 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Status</th>
                        <th class="px-6 sm:px-7 py-3 text-left text-xs font-bold text-[#8b9790] uppercase tracking-wider">Penyakit Terkait</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f4f1] bg-white">
                    @foreach($komoditas as $item)
                    @php($row = is_object($item) ? (array) $item : $item)
                    <tr class="hover:bg-[#f7faf8] transition-colors">
                        <td class="px-6 sm:px-7 py-4 text-sm font-mono font-semibold text-[#176b45]">{{ $row['kode'] ?? '-' }}</td>
                        <td class="px-6 sm:px-7 py-4 text-sm font-semibold text-[#173b29]">{{ $row['nama'] ?? '-' }}</td>
                        <td class="px-6 sm:px-7 py-4 text-sm italic text-[#8b9790]">{{ $row['nama_latin'] ?? '-' }}</td>
                        <td class="px-6 sm:px-7 py-4">
                            @if($row['is_active'] ?? false)
                                <span class="inline-flex items-center rounded-full bg-[#e8f4ed] px-2.5 py-0.5 text-xs font-semibold text-[#176b45]">Aktif</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-[#f0f4f1] px-2.5 py-0.5 text-xs font-semibold text-[#8b9790]">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 sm:px-7 py-4 text-sm text-[#526159]">
                            @if(($komoditasDipakai[$row['id']] ?? 0) > 0)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#eef5f7] px-2.5 py-0.5 text-xs font-medium text-[#3d6b78]">{{ $komoditasDipakai[$row['id']] ?? 0 }} penyakit</span>
                            @else
                                <span class="text-xs text-[#b0bab3]">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-16">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#f3f8f4] text-[#b0c4ba] mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
            <p class="text-sm font-medium text-[#8b9790]">Belum ada data komoditas tersedia.</p>
            <p class="text-xs text-[#b0bab3] mt-1">Pastikan service integrasi komoditas berjalan.</p>
        </div>
        @endif
    </div>
</div>
@endsection
