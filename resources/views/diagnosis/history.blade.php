@extends('layouts.app')

@section('title', 'Riwayat Diagnosis')

@php
    $komoditasNama = fn (int $commodityId): string => $komoditasMap[$commodityId] ?? ('Komoditas #'.$commodityId);
    $sortOptions = [
        'terbaru' => 'Terbaru',
        'terlama' => 'Terlama',
        'kode' => 'Kode Diagnosis (A–Z)',
        'komoditas' => 'Komoditas (A–Z)',
        'cf' => 'Nilai CF (Tertinggi)',
        'penyakit' => 'Penyakit Utama (A–Z)',
    ];
    $hasFilters = $search !== '' || $komoditasFilter > 0 || $tanggal !== '';
@endphp

@section('content')
    <x-page-header
        title="Riwayat Diagnosis"
        subtitle="Daftar diagnosis yang pernah Anda jalankan beserta hasilnya."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Riwayat Diagnosis'],
        ]"
    />

    {{-- Filter bar (search, komoditas, tanggal, sorting) + loading state --}}
    <div class="mb-6" x-data="{ submitting: false }">
        <x-card>
            <form method="GET" action="{{ route('diagnosis.history') }}" @submit="submitting = true"
                  class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="filter-q" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Cari</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9ba8a1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="search" name="q" id="filter-q" value="{{ $search }}"
                               placeholder="Kode diagnosis atau nama penyakit…"
                               class="w-full rounded-xl border border-[#dbe5df] bg-white py-2.5 pl-10 pr-3 text-sm text-[#173b29] placeholder:text-[#a0aba4] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                    </div>
                </div>
                <div>
                    <label for="filter-komoditas" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Komoditas</label>
                    <select name="komoditas" id="filter-komoditas"
                            class="w-full rounded-xl border border-[#dbe5df] bg-white py-2.5 px-3 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                        <option value="">Semua Komoditas</option>
                        @foreach ($komoditasMap as $id => $nama)
                            <option value="{{ $id }}" @selected($komoditasFilter === (int) $id)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-tanggal" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal</label>
                    <input type="date" name="tanggal" id="filter-tanggal" value="{{ $tanggal }}"
                           class="w-full rounded-xl border border-[#dbe5df] bg-white py-2.5 px-3 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                </div>
                <div>
                    <label for="filter-sort" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Urutkan</label>
                    <select name="sort" id="filter-sort"
                            class="w-full rounded-xl border border-[#dbe5df] bg-white py-2.5 px-3 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-5">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Terapkan Filter
                    </button>
                    <a href="{{ route('diagnosis.history') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Reset
                    </a>
                    @if ($hasFilters)
                        <p class="ml-auto text-xs font-semibold text-[#8a9990]">{{ $diagnoses->total() }} hasil ditemukan</p>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Loading state: tampil saat form filter dikirim --}}
        <div x-show="submitting" x-cloak
             class="pointer-events-none fixed inset-0 z-[90] flex items-center justify-center bg-[#f7faf8]/60">
            <span class="flex items-center gap-3 rounded-full bg-white px-5 py-3 shadow-lg">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-[#e4ece7] border-t-[#176b45]"></span>
                <span class="text-sm font-semibold text-[#66746c]">Memuat…</span>
            </span>
        </div>
    </div>

    {{-- Error state: referensi komoditas tidak dapat dimuat --}}
    @if ($komoditasError)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div>
                <p class="font-semibold">Referensi komoditas sedang tidak dapat dimuat.</p>
                <p class="mt-0.5 text-amber-600">Nama komoditas ditampilkan sementara sebagai "#id". Data diagnosis Anda tetap utuh.</p>
            </div>
        </div>
    @endif

    @if ($diagnoses->isEmpty())
        <x-card class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#e8f4ed] text-[#176b45]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </span>
            <h2 class="mt-4 text-lg font-bold text-[#173b29]">
                {{ $hasFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada riwayat diagnosis' }}
            </h2>
            <p class="mt-1 max-w-md text-sm text-[#66746c]">
                @if ($hasFilters)
                    Coba ubah kata kunci, komoditas, tanggal, atau urutan yang Anda pilih.
                @else
                    Anda belum pernah menjalankan diagnosis. Mulai diagnosis tanaman pertama Anda.
                @endif
            </p>
            @if ($hasFilters)
                <a href="{{ route('diagnosis.history') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    Reset Filter
                </a>
            @else
                <a href="{{ route('diagnosis.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4" /></svg>
                    Diagnosis Sekarang
                </a>
            @endif
        </x-card>
    @else
        {{-- Tampilan tablet/desktop --}}
        <x-card class="hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead>
                        <tr class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">
                            <th class="px-5 py-3">Kode Diagnosis</th>
                            <th class="px-3 py-3">Tanggal</th>
                            <th class="px-3 py-3">Komoditas</th>
                            <th class="px-3 py-3">Diagnosis/Penyakit</th>
                            <th class="px-3 py-3 text-right">Nilai CF</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eef3ef]">
                        @foreach ($diagnoses as $diagnosis)
                            @php $top = $diagnosis->results->first(); @endphp
                            <tr class="transition-colors hover:bg-[#fafcfb]">
                                <td class="px-5 py-4">
                                    <span class="font-bold text-[#176b45]">{{ $diagnosis->kode }}</span>
                                    <span class="block text-xs text-[#8a9990]">#{{ $diagnosis->id }}</span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="font-medium text-[#173b29]">{{ $diagnosis->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y') ?? '—' }}</span>
                                    <span class="block text-xs text-[#8a9990]">{{ $diagnosis->created_at?->timezone('Asia/Jakarta')->translatedFormat('H:i') ?? '' }}</span>
                                </td>
                                <td class="px-3 py-4 font-medium text-[#173b29]">{{ $komoditasNama((int) $diagnosis->commodity_id) }}</td>
                                <td class="px-3 py-4">
                                    @if ($top === null)
                                        <span class="text-[#8a9990]">Tidak ada hasil</span>
                                    @else
                                        <span class="font-semibold text-[#173b29]">{{ $top->disease_name_snapshot }}</span>
                                        <span class="block text-xs text-[#8a9990]">dari {{ $diagnosis->results->count() }} kandidat</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-right">
                                    @if ($top !== null)
                                        <span class="font-bold text-[#176b45]">{{ number_format((float) $top->cf_value, 2, ',', '.') }}</span>
                                        <span class="block text-[10px] text-[#8a9990]">{{ round(max(0.0, (float) $top->cf_value) * 100, 2) }}%</span>
                                    @else
                                        <span class="text-[#8a9990]">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('diagnosis.show', $diagnosis->id) }}"
                                       class="inline-flex items-center gap-1 rounded-lg border border-[#dbe5df] bg-white px-3 py-1.5 text-xs font-semibold text-[#176b45] hover:bg-[#f3f8f4]">
                                        Detail
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Tampilan mobile (stacked cards) --}}
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @foreach ($diagnoses as $diagnosis)
                @php $top = $diagnosis->results->first(); @endphp
                <x-card class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-[#176b45]">{{ $diagnosis->kode }}</p>
                            <p class="text-sm font-semibold text-[#173b29]">{{ $komoditasNama((int) $diagnosis->commodity_id) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-[#176b45]">
                                {{ $top !== null ? number_format((float) $top->cf_value, 2, ',', '.') : '—' }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-[#66746c]">
                        @if ($top === null)
                            Tidak ada hasil
                        @else
                            {{ $top->disease_name_snapshot }} <span class="text-xs text-[#8a9990]">· {{ $diagnosis->results->count() }} kandidat</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-[#8a9990]">{{ $diagnosis->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') ?? '—' }}</p>
                    <a href="{{ route('diagnosis.show', $diagnosis->id) }}"
                       class="mt-3 inline-flex items-center gap-1 rounded-lg border border-[#dbe5df] bg-white px-3 py-1.5 text-xs font-semibold text-[#176b45] hover:bg-[#f3f8f4]">
                        Detail
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-medium text-[#8a9990]">
                Menampilkan {{ $diagnoses->firstItem() ?? 0 }}–{{ $diagnoses->lastItem() ?? 0 }} dari {{ $diagnoses->total() }} diagnosis
            </p>
            <div>
                {{ $diagnoses->links() }}
            </div>
        </div>
    @endif
@endsection