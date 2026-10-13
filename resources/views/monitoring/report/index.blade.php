@extends('layouts.app')

@section('title', 'Laporan Monitoring')

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    <x-page-header
        title="Laporan Monitoring"
        subtitle="Rekap data kasus dan penanganan perkebunan berdasarkan periode dan kriteria monitoring."
        :breadcrumbs="[
            ['label' => 'Monitoring', 'url' => route('webgis.index')],
            ['label' => 'Laporan Monitoring'],
        ]"
    />

    <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 sm:p-6" aria-labelledby="report-filter-heading">
        <div class="mb-5">
            <h2 id="report-filter-heading" class="text-base font-bold text-[#173b29]">Filter Laporan</h2>
            <p class="mt-1 text-xs text-[#89968e]">Gunakan filter untuk mempersempit data yang ingin dianalisis.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-[#f0c5c0] bg-[#fff8f7] px-4 py-3 text-sm text-[#8d3d35]" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="GET" action="{{ route('monitoring.report.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <label class="text-xs font-semibold text-[#526159]">
                    Tanggal Mulai
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="mt-2 w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm font-normal text-[#526159] outline-none focus:border-[#176b45] focus:ring-2 focus:ring-[#b8d7c3]">
                </label>
                <label class="text-xs font-semibold text-[#526159]">
                    Tanggal Selesai
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="mt-2 w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm font-normal text-[#526159] outline-none focus:border-[#176b45] focus:ring-2 focus:ring-[#b8d7c3]">
                </label>
                <label class="text-xs font-semibold text-[#526159]">
                    Kabupaten/Kota
                    <select name="regency" class="mt-2 w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm font-normal text-[#526159] outline-none focus:border-[#176b45] focus:ring-2 focus:ring-[#b8d7c3]">
                        <option value="">Semua Kabupaten/Kota</option>
                        @foreach ($options['regencies'] as $regency)
                            <option value="{{ $regency }}" @selected(request('regency') === $regency)>{{ $regency }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-[#526159]">
                    Komoditas
                    <select name="commodity" class="mt-2 w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm font-normal text-[#526159] outline-none focus:border-[#176b45] focus:ring-2 focus:ring-[#b8d7c3]">
                        <option value="">Semua Komoditas</option>
                        @foreach ($options['commodities'] as $commodity)
                            <option value="{{ $commodity }}" @selected(request('commodity') === $commodity)>{{ $commodity }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-[#526159]">
                    Status
                    <select name="status" class="mt-2 w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm font-normal text-[#526159] outline-none focus:border-[#176b45] focus:ring-2 focus:ring-[#b8d7c3]">
                        <option value="">Semua Status</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto] lg:items-end">
                <label class="text-xs font-semibold text-[#526159]">
                    Penyakit
                    <select name="disease" class="mt-2 w-full rounded-lg border border-[#d6e0d9] bg-[#f7faf8] px-3 py-2.5 text-sm font-normal text-[#526159] outline-none focus:border-[#176b45] focus:ring-2 focus:ring-[#b8d7c3]">
                        <option value="">Semua Penyakit</option>
                        @foreach ($options['diseases'] as $disease)
                            <option value="{{ $disease }}" @selected(request('disease') === $disease)>{{ $disease }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2 text-xs font-semibold text-[#526159]">
                    <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue')) class="rounded border-[#d6e0d9] text-[#176b45]">
                    Hanya Melewati Target Waktu
                </label>
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-action-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-action-primary-hover focus:outline-none focus:ring-2 focus:ring-[#b8d7c3] sm:w-auto">
                    Terapkan Filter
                </button>
                <a href="{{ route('monitoring.report.index') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-[#d6e0d9] bg-white px-4 py-2.5 text-sm font-semibold text-[#526159] transition hover:border-[#176b45] hover:text-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3] sm:w-auto">
                    Reset Filter
                </a>
            </div>
        </form>
    </section>

    <section aria-labelledby="report-summary-heading">
        <div class="mb-3">
            <h2 id="report-summary-heading" class="text-base font-bold text-[#173b29]">Ringkasan</h2>
            <p class="mt-1 text-xs text-[#89968e]">Ringkasan mengikuti filter yang sedang diterapkan.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Total Permohonan</p>
                <p class="mt-3 text-3xl font-bold text-[#173b29]">{{ $summary['total'] }}</p>
                <p class="mt-1 text-xs text-[#77847c]">Satu entri per permohonan/kasus sesuai filter</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Menunggu Review</p>
                <p class="mt-3 text-3xl font-bold text-[#3869a1]">{{ $summary['review'] }}</p>
                <p class="mt-1 text-xs text-[#77847c]">Menunggu keputusan atau tinjauan data historis</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Dalam Penanganan</p>
                <p class="mt-3 text-3xl font-bold text-[#176b45]">{{ $summary['active'] }}</p>
                <p class="mt-1 text-xs text-[#77847c]">Dalam Penanganan; POPT dan target sudah ditetapkan</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Selesai</p>
                <p class="mt-3 text-3xl font-bold text-[#526159]">{{ $summary['completed'] }}</p>
                <p class="mt-1 text-xs text-[#77847c]">Status selesai</p>
            </article>
            <article class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#89968e]">Melewati Target Waktu</p>
                <p class="mt-3 text-3xl font-bold text-[#a83d32]">{{ $summary['overdue'] }}</p>
                <p class="mt-1 text-xs text-[#77847c]">Indikator tambahan dari kasus aktif</p>
            </article>
        </div>
    </section>

    <section class="soft-card overflow-hidden rounded-xl border border-[#e6eee8] bg-white" aria-labelledby="report-table-heading">
        <div class="flex flex-col gap-1 border-b border-[#eef3ef] px-5 py-4 sm:px-6">
            <h2 id="report-table-heading" class="text-base font-bold text-[#173b29]">Tabel Laporan Monitoring</h2>
            <p class="text-xs text-[#89968e]">Kasus terbaru ditampilkan terlebih dahulu.</p>
        </div>

        <div class="overflow-x-auto" role="region" tabindex="0" aria-label="Tabel laporan monitoring">
            <table class="min-w-[1120px] divide-y divide-[#eef3ef] text-sm">
                <thead class="bg-[#f7faf8] text-xs font-semibold text-[#66746c]">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Kode Kasus</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Kode Permohonan</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Tanggal</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Kelompok Tani</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Kabupaten/Kota</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Komoditas</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Penyakit</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">POPT</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Target Penyelesaian</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left">Status Penanganan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f4f1] text-[#526159]">
                    @forelse ($kasus as $item)
                        @php($monitoring = $statusPresentations[$item['monitoring_status']])
                        <tr class="align-top transition-colors hover:bg-[#fbfdfb]">
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-xs font-semibold text-[#173b29]">{{ $item['kasus_code'] ?? 'Belum menjadi kasus' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-xs">{{ $item['permohonan_code'] }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs">{{ \Illuminate\Support\Carbon::parse($item['monitoring_recorded_at'])->timezone('Asia/Jakarta')->format('d M Y') }}</td>
                            <td class="max-w-[190px] px-4 py-4">{{ $item['kelompok_tani']['nama'] ?? 'Belum tersedia' }}</td>
                            <td class="max-w-[180px] px-4 py-4">{{ $item['wilayah']['kabupaten'] ?? 'Belum tersedia' }}</td>
                            <td class="max-w-[180px] px-4 py-4">{{ $item['komoditas']['nama'] ?? 'Belum tersedia' }}</td>
                            <td class="max-w-[200px] px-4 py-4">{{ $item['penyakit']['nama'] ?? 'Belum tersedia' }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $item['penugasan_popt']['nama'] ?? 'Belum ditugaskan' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs">{{ $item['effective_deadline_at'] ? \Illuminate\Support\Carbon::parse($item['effective_deadline_at'])->timezone('Asia/Jakarta')->format('d M Y H:i') : 'Belum tersedia' }}</td>
                            <td class="px-4 py-4">
                                <x-kasus.handling-status-badge :status="$monitoring" />
                                <x-kasus.overdue-indicator :is-overdue="$item['is_overdue']" class="mt-2" />
                                @if($item['rejection_reason'])<p class="mt-2 text-xs text-[#66746c]">Alasan Penolakan: {{ $item['rejection_reason'] }}</p>@endif
                                @if($item['legacy_note'] ?? null)<p class="mt-2 text-xs text-amber-800">{{ $item['legacy_note'] }} {{ $item['legacy_reason'] }}</p>@endif
                                @if($item['history_note'] ?? null)<p class="mt-2 text-xs text-amber-800">{{ $item['history_note'] }}</p>@endif
                                @if($item['completion_evidence_missing'] ?? false)<p class="mt-2 text-xs text-amber-800">Data selesai lama: laporan akhir atau bukti foto belum tersedia.</p>@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-14 text-center text-sm text-[#77847c]">Tidak ada data monitoring yang sesuai dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kasus->hasPages())
            <div class="border-t border-[#eef3ef] px-5 py-4 sm:px-6">
                {{ $kasus->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
