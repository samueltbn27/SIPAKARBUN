@extends('layouts.app')

@section('title', 'Dashboard Monitoring')
@section('subtitle', 'Ringkasan read-only persebaran dan penanganan kasus perkebunan.')

@section('content')
<div data-monitoring-dashboard class="mx-auto max-w-[1500px] space-y-6">
    <p data-dashboard-loading class="rounded-xl border border-[#d6e0d9] bg-[#f7faf8] px-5 py-4 text-center text-sm font-medium text-[#526159]" role="status">
        Memuat data monitoring...
    </p>
    <p data-dashboard-error hidden class="rounded-xl border border-[#e4c5c0] bg-[#fff8f7] px-5 py-4 text-center text-sm font-medium text-[#8d3d35]" role="alert">
        Data monitoring tidak dapat dimuat.
    </p>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-[#8c9890]">
                <span>Monitoring</span>
                <span aria-hidden="true">/</span>
                <span class="text-[#176b45]">Dashboard</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-[#173b29] sm:text-[28px]">Dashboard Monitoring</h1>
            <p class="mt-1 text-sm text-[#77847c]">Ringkasan read-only dari data kasus yang sama dengan WebGIS.</p>
        </div>
        <a href="{{ route('webgis.index') }}"
           class="inline-flex w-fit items-center justify-center rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#125437] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3]">
            Lihat WebGIS
            <span class="ml-2" aria-hidden="true">→</span>
        </a>
    </div>

    <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6" aria-labelledby="monitoring-filter-heading">
        <div class="mb-5 flex flex-col gap-1">
            <h2 id="monitoring-filter-heading" class="text-base font-bold text-[#173b29]">Filter Monitoring</h2>
            <p class="text-xs text-[#89968e]">Filter diterapkan bersama dengan semantics AND pada seluruh KPI dan chart.</p>
            <span data-dashboard-active-filter-count class="mt-2 inline-flex w-fit rounded-full bg-[#eef6f1] px-3 py-1 text-xs font-semibold text-[#2d6b4a]" aria-live="polite">Filter aktif: 0</span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @php
                $dashboardFilters = [
                    ['id' => 'regency', 'label' => 'Kabupaten/Kota'],
                    ['id' => 'district', 'label' => 'Kecamatan'],
                    ['id' => 'commodity', 'label' => 'Komoditas'],
                    ['id' => 'disease', 'label' => 'Penyakit'],
                    ['id' => 'status', 'label' => 'Status'],
                    ['id' => 'popt', 'label' => 'POPT'],
                ];
            @endphp

            @foreach($dashboardFilters as $filter)
                <div>
                    <label for="monitoring-{{ $filter['id'] }}" class="mb-2 block text-xs font-semibold text-[#526159]">{{ $filter['label'] }}</label>
                    <select id="monitoring-{{ $filter['id'] }}" name="{{ $filter['id'] }}"
                            data-dashboard-filter="{{ $filter['id'] }}"
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
            <button type="button" data-dashboard-reset
                    class="inline-flex items-center justify-center rounded-lg border border-[#d6e0d9] bg-white px-4 py-2.5 text-sm font-semibold text-[#526159] transition hover:border-[#176b45] hover:text-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3]">
                Reset Filter
            </button>
        </div>
    </section>

    <section aria-labelledby="monitoring-kpi-heading">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 id="monitoring-kpi-heading" class="text-base font-bold text-[#173b29]">Ringkasan Kasus</h2>
                <p class="mt-1 text-xs text-[#89968e]">Angka mengikuti kombinasi filter yang sedang aktif.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Total Kasus</p>
                <p data-dashboard-kpi="total" class="mt-3 text-3xl font-bold text-[#173b29]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Kasus yang sesuai filter</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Kasus Aktif</p>
                <p data-dashboard-kpi="active" class="mt-3 text-3xl font-bold text-[#176b45]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Seluruh status selain selesai</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Selesai</p>
                <p data-dashboard-kpi="completed" class="mt-3 text-3xl font-bold text-[#526159]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Status completed</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Ditunda</p>
                <p data-dashboard-kpi="postponed" class="mt-3 text-3xl font-bold text-[#80610a]">0</p>
                <p class="mt-1 text-xs text-[#77847c]">Status postponed</p>
            </article>
        </div>
    </section>

    <p data-dashboard-dataset-empty hidden class="rounded-xl border border-dashed border-[#d6e0d9] bg-[#f7faf8] px-5 py-4 text-center text-sm font-medium text-[#526159]" role="status">
        Belum ada data kasus penanganan.
    </p>
    <p data-dashboard-empty hidden class="rounded-xl border border-dashed border-[#d6e0d9] bg-[#f7faf8] px-5 py-4 text-center text-sm font-medium text-[#526159]" role="status">
        Tidak ada kasus yang sesuai dengan filter yang dipilih.
    </p>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2" aria-label="Visualisasi monitoring kasus">
        <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">Kasus per Status</h2>
            <p data-dashboard-chart-summary="status" class="mt-1 min-h-10 text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan status...</p>
            <div class="relative mt-4 h-[300px]">
                <canvas data-dashboard-chart="status" role="img" aria-label="Diagram jumlah kasus berdasarkan status"></canvas>
            </div>
        </article>

        <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">Kasus per Komoditas</h2>
            <p data-dashboard-chart-summary="commodity" class="mt-1 min-h-10 text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan komoditas...</p>
            <div class="relative mt-4 h-[300px]">
                <canvas data-dashboard-chart="commodity" role="img" aria-label="Diagram jumlah kasus berdasarkan komoditas"></canvas>
            </div>
        </article>

        <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">Kasus per Kabupaten/Kota</h2>
            <p data-dashboard-chart-summary="regency" class="mt-1 min-h-10 text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan wilayah...</p>
            <div class="relative mt-4 h-[300px]">
                <canvas data-dashboard-chart="regency" role="img" aria-label="Diagram jumlah kasus berdasarkan kabupaten atau kota"></canvas>
            </div>
        </article>

        <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">Kasus per Penyakit</h2>
            <p data-dashboard-chart-summary="disease" class="mt-1 min-h-10 text-xs leading-5 text-[#89968e]">Menyiapkan ringkasan penyakit...</p>
            <div class="relative mt-4 h-[300px]">
                <canvas data-dashboard-chart="disease" role="img" aria-label="Diagram jumlah kasus berdasarkan penyakit"></canvas>
            </div>
        </article>
    </section>
</div>
@endsection
