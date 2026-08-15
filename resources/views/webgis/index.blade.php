@extends('layouts.app')

@section('title', 'WebGIS Penanganan Kasus')
@section('subtitle', 'Pantau persebaran dan perkembangan penanganan kasus perkebunan.')

@section('content')
@php
    $filters = [
        ['id' => 'kabupaten', 'label' => 'Kabupaten'],
        ['id' => 'kecamatan', 'label' => 'Kecamatan'],
        ['id' => 'komoditas', 'label' => 'Komoditas'],
        ['id' => 'penyakit', 'label' => 'Penyakit'],
        ['id' => 'status', 'label' => 'Status'],
        ['id' => 'popt', 'label' => 'POPT'],
    ];

    $statusLegend = [
        ['label' => 'Ditugaskan', 'class' => 'bg-[#8b6cc7]'],
        ['label' => 'Sedang Direview', 'class' => 'bg-[#3d8eb9]'],
        ['label' => 'Ditunda', 'class' => 'bg-[#b8860b]'],
        ['label' => 'Siap Dieksekusi', 'class' => 'bg-[#5a8d6c]'],
        ['label' => 'Dalam Pelaksanaan', 'class' => 'bg-[#176b45]'],
        ['label' => 'Selesai', 'class' => 'bg-[#526159]'],
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
            <p class="text-xs text-[#89968e]">Filter akan tersedia setelah data kasus terhubung.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
            @foreach($filters as $filter)
                <div>
                    <label for="{{ $filter['id'] }}" class="block mb-2 text-xs font-semibold text-[#526159]">{{ $filter['label'] }}</label>
                    <select id="{{ $filter['id'] }}" name="{{ $filter['id'] }}" disabled
                            class="w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm text-[#89968e] outline-none disabled:cursor-not-allowed disabled:opacity-80">
                        <option>Belum tersedia</option>
                    </select>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" disabled
                    class="inline-flex items-center justify-center rounded-lg border border-[#d6e0d9] bg-white px-4 py-2.5 text-sm font-semibold text-[#89968e] disabled:cursor-not-allowed disabled:opacity-70">
                Reset Filter
            </button>
        </div>
    </section>

    <section class="soft-card overflow-hidden rounded-xl border border-[#e6eee8] bg-white" aria-labelledby="map-heading">
        <div class="flex items-center justify-between gap-3 border-b border-[#eef3ef] px-5 py-4 sm:px-6">
            <div>
                <h2 id="map-heading" class="text-base font-bold text-[#173b29]">Peta Persebaran Kasus</h2>
                <p class="mt-1 text-xs text-[#89968e]">Lokasi kasus akan ditampilkan berdasarkan koordinat penanganan.</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-[#f0f4f1] px-3 py-1 text-xs font-medium text-[#89968e]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#b0bab3]" aria-hidden="true"></span>
                Belum terhubung
            </span>
        </div>

        <div role="img" aria-label="Placeholder peta WebGIS"
             class="flex min-h-[320px] items-center justify-center bg-[linear-gradient(135deg,#f7faf8_25%,#eef5f0_25%,#eef5f0_50%,#f7faf8_50%,#f7faf8_75%,#eef5f0_75%)] bg-[length:28px_28px] px-6 py-16 sm:min-h-[420px]">
            <div class="max-w-md text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-[#176b45] shadow-sm ring-1 ring-[#e6eee8]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 20.25 3.75 18V5.25L9 3.75l6 2.25 5.25-2.25V18L15 20.25 9 18.75V3.75m0 15 6 1.5m0-15v15"/>
                    </svg>
                </span>
                <h3 class="mt-5 text-base font-bold text-[#173b29]">Peta WebGIS akan ditampilkan di sini</h3>
                <p class="mt-2 text-sm leading-6 text-[#77847c]">Integrasi peta dan data kasus akan dilakukan pada vertical slice berikutnya.</p>
            </div>
        </div>
    </section>

    <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6" aria-labelledby="legend-heading">
        <div class="flex flex-col gap-1 mb-5">
            <h2 id="legend-heading" class="text-base font-bold text-[#173b29]">Status Penanganan</h2>
            <p class="text-xs text-[#89968e]">Legenda status sementara untuk rancangan monitoring.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($statusLegend as $status)
                <div class="flex items-center gap-3 rounded-lg border border-[#eef3ef] bg-[#f7faf8] px-3 py-2.5">
                    <span class="h-3 w-3 flex-shrink-0 rounded-full {{ $status['class'] }}" aria-hidden="true"></span>
                    <span class="text-sm font-medium text-[#526159]">{{ $status['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
