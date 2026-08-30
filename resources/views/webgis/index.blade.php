@extends('layouts.app')

@section('title', 'WebGIS Penanganan Kasus')
@section('subtitle', 'Pantau persebaran dan perkembangan penanganan kasus perkebunan.')

@section('content')
@php
    $filters = [
        ['id' => 'regency', 'label' => 'Kabupaten/Kota'],
        ['id' => 'district', 'label' => 'Kecamatan'],
        ['id' => 'commodity', 'label' => 'Komoditas'],
        ['id' => 'disease', 'label' => 'Penyakit'],
        ['id' => 'status', 'label' => 'Status'],
        ['id' => 'popt', 'label' => 'POPT'],
    ];

@endphp

<div class="max-w-[1500px] mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2">
            <span>Monitoring</span>
            <span aria-hidden="true">/</span>
            <span class="text-[#176b45]">WebGIS</span>
        </div>
        <h1 class="text-2xl sm:text-[28px] font-bold tracking-tight text-[#173b29]">WebGIS Penanganan Kasus</h1>
        <p class="mt-1 text-sm text-[#77847c]">Pantau persebaran dan perkembangan penanganan kasus perkebunan.</p>
    </div>

    <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6" aria-labelledby="filter-heading">
        <div class="flex flex-col gap-1 mb-5">
            <h2 id="filter-heading" class="text-base font-bold text-[#173b29]">Filter Monitoring</h2>
            <p class="text-xs text-[#89968e]">Gunakan satu atau beberapa filter untuk mempersempit lokasi kasus tanpa memuat ulang halaman.</p>
            <span data-webgis-active-filter-count class="mt-2 inline-flex w-fit rounded-full bg-[#eef6f1] px-3 py-1 text-xs font-semibold text-[#2d6b4a]" aria-live="polite">Filter aktif: 0</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
            @foreach($filters as $filter)
                <div>
                    <label for="{{ $filter['id'] }}" class="block mb-2 text-xs font-semibold text-[#526159]">{{ $filter['label'] }}</label>
                    <select id="{{ $filter['id'] }}" name="{{ $filter['id'] }}"
                            data-webgis-filter="{{ $filter['id'] }}"
                            @disabled($filter['id'] === 'district')
                            class="w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm text-[#526159] outline-none disabled:cursor-not-allowed disabled:opacity-80">
                        @if($filter['id'] === 'status')
                            <option value="">Semua Status</option>
                        @elseif($filter['id'] === 'district')
                            <option value="">Pilih Kabupaten terlebih dahulu</option>
                        @else
                            <option value="">Memuat opsi...</option>
                        @endif
                    </select>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" data-webgis-reset
                    class="inline-flex items-center justify-center rounded-lg border border-[#d6e0d9] bg-white px-4 py-2.5 text-sm font-semibold text-[#526159] transition hover:border-[#176b45] hover:text-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3]">
                Reset Filter
            </button>
        </div>
    </section>

    <section class="soft-card overflow-hidden rounded-xl border border-[#e6eee8] bg-white" aria-labelledby="map-heading">
        <div class="flex items-center justify-between gap-3 border-b border-[#eef3ef] px-5 py-4 sm:px-6">
            <div>
                <h2 id="map-heading" class="text-base font-bold text-[#173b29]">Peta Persebaran Kasus</h2>
            <p class="mt-1 text-xs text-[#89968e]" data-webgis-case-count>Menyiapkan data kasus...</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-[#eef6f1] px-3 py-1 text-xs font-medium text-[#2d6b4a]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#176b45]" aria-hidden="true"></span>
                API aktif
            </span>
        </div>

        <div id="webgis-map" data-webgis-map
             class="relative h-[320px] w-full bg-[#eef5f0] sm:h-[500px]" aria-label="Peta lokasi penanganan kasus">
            <p data-webgis-loading class="absolute inset-0 z-[400] flex items-center justify-center bg-[#eef5f0] px-6 text-center text-sm font-medium text-[#526159]">
                Memuat data kasus...
            </p>
            <p data-webgis-error hidden class="absolute inset-0 z-[400] flex items-center justify-center bg-[#eef5f0] px-6 text-center text-sm font-medium text-[#8d3d35]">
                Data WebGIS tidak dapat dimuat.
            </p>
            <noscript>
                <div class="flex h-full items-center justify-center px-6 text-center text-sm text-[#77847c]">
                    JavaScript diperlukan untuk menampilkan peta WebGIS.
                </div>
            </noscript>
            <p data-webgis-dataset-empty hidden class="pointer-events-none absolute inset-0 z-[400] flex items-center justify-center px-6 text-center text-sm font-medium text-[#526159]">
                Belum ada data kasus penanganan.
            </p>
            <p data-webgis-empty hidden class="pointer-events-none absolute inset-0 z-[400] flex items-center justify-center px-6 text-center text-sm font-medium text-[#526159]">
                Tidak ada kasus yang sesuai dengan filter yang dipilih.
            </p>
        </div>
    </section>

    <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6" aria-labelledby="legend-heading">
        <div class="flex flex-col gap-1 mb-5">
            <h2 id="legend-heading" class="text-base font-bold text-[#173b29]">Status Penanganan</h2>
            <p class="text-xs text-[#89968e]">Simbol marker menunjukkan status terakhir setiap kasus.</p>
        </div>
        <div data-webgis-status-legend class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <noscript>
                <p class="text-sm text-[#77847c]">JavaScript diperlukan untuk menampilkan legenda status.</p>
            </noscript>
        </div>
    </section>
</div>
<div data-case-detail-backdrop hidden class="fixed inset-0 z-[1000] bg-[#173b29]/30 backdrop-blur-[1px]" aria-hidden="true"></div>

<aside id="case-detail-drawer" data-case-detail-drawer
       class="fixed inset-y-0 right-0 z-[1100] flex w-full max-w-lg translate-x-full flex-col border-l border-[#e6eee8] bg-white shadow-2xl transition-transform duration-200 ease-out"
       aria-hidden="true" inert aria-labelledby="case-detail-heading" role="dialog">
    <header class="flex items-start justify-between gap-4 border-b border-[#eef3ef] px-5 py-4 sm:px-6">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Detail Kasus</p>
            <h2 id="case-detail-heading" data-case-detail="case-code" tabindex="-1" class="mt-1 truncate text-lg font-bold text-[#173b29]">-</h2>
        </div>
        <button type="button" data-case-detail-close
                class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-2xl leading-none text-[#526159] transition hover:bg-[#f0f5f1] hover:text-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3]"
                aria-label="Tutup detail kasus">
            <span aria-hidden="true">&times;</span>
        </button>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
        <section aria-labelledby="case-summary-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 id="case-summary-heading" class="text-sm font-bold text-[#173b29]">Ringkasan Kasus</h3>
                <span data-case-detail="status">-</span>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-lg bg-[#fffaf0] p-3"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Status permohonan</dt><dd data-case-detail="request-status" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Status penanganan</dt><dd data-case-detail="handling-status" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3 sm:col-span-2"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Poktan</dt><dd data-case-detail="kelompok-tani" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Komoditas</dt><dd data-case-detail="commodity" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Kode komoditas</dt><dd data-case-detail="commodity-code" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3 sm:col-span-2"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Penyakit</dt><dd data-case-detail="disease" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Kabupaten/Kota</dt><dd data-case-detail="regency" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Kecamatan</dt><dd data-case-detail="district" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg bg-[#f7faf8] p-3 sm:col-span-2"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">POPT</dt><dd data-case-detail="popt" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
            </dl>
        </section>

        <section class="mt-6 border-t border-[#eef3ef] pt-5" aria-labelledby="case-location-heading">
            <h3 id="case-location-heading" class="text-sm font-bold text-[#173b29]">Lokasi Kasus</h3>
            <p class="mt-1 text-xs text-[#89968e]">Koordinat lokasi penanganan, bukan lokasi Poktan.</p>
            <dl class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-[#eef3ef] px-3 py-2.5"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Latitude</dt><dd data-case-detail="latitude" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
                <div class="rounded-lg border border-[#eef3ef] px-3 py-2.5"><dt class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Longitude</dt><dd data-case-detail="longitude" class="mt-1 text-sm font-semibold text-[#526159]">-</dd></div>
            </dl>
        </section>

        <section class="mt-6 border-t border-[#eef3ef] pt-5" aria-labelledby="case-update-heading">
            <h3 id="case-update-heading" class="text-sm font-bold text-[#173b29]">Pembaruan Terakhir</h3>
            <p data-case-detail="updated-at" class="mt-2 text-sm text-[#526159]">-</p>
            <div class="mt-3 rounded-lg border border-[#eef3ef] bg-[#f7faf8] p-3"><p class="text-[10px] font-semibold uppercase tracking-wide text-[#89968e]">Catatan terakhir</p><p data-case-detail="last-note" class="mt-1 text-sm leading-6 text-[#526159]">-</p></div>
        </section>

        <section class="mt-6 border-t border-[#eef3ef] pt-5" aria-labelledby="case-timeline-heading">
            <h3 id="case-timeline-heading" class="text-sm font-bold text-[#173b29]">Riwayat Penanganan</h3>
            <ol data-case-detail-timeline class="mt-4"></ol>
        </section>
    </div>
</aside>
@endsection
