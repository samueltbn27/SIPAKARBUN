@extends('layouts.app')

@section('title', 'Permohonan Saya')

@php
    $statusLabels = [
        'diajukan' => ['Diajukan', 'bg-blue-50 text-blue-700'],
        'sedang_direview' => ['Sedang Direview', 'bg-amber-50 text-amber-700'],
        'diterima' => ['Diterima', 'bg-[#e8f4ed] text-[#176b45]'],
        'ditolak' => ['Ditolak', 'bg-red-50 text-red-700'],
    ];
    $penangananLabels = [
        'diterima' => ['Belum Ditugaskan', 'bg-[#eef3ef] text-[#66746c]'],
        'sedang_direview' => ['Sedang Direview', 'bg-amber-50 text-amber-700'],
        'ditugaskan' => ['Ditugaskan', 'bg-blue-50 text-blue-700'],
        'ditunda' => ['Ditunda', 'bg-orange-50 text-orange-700'],
        'siap_dieksekusi' => ['Siap Dieksekusi', 'bg-[#e8f4ed] text-[#176b45]'],
        'dalam_pelaksanaan' => ['Dalam Pelaksanaan', 'bg-[#176b45] text-white'],
        'selesai' => ['Selesai', 'bg-[#173b29] text-white'],
    ];
    $komoditasNama = fn (int $commodityId): string => $komoditasMap[$commodityId] ?? ('Komoditas #'.$commodityId);
    $status = fn (string $value): array => $statusLabels[$value] ?? [\Illuminate\Support\Str::headline($value), 'bg-[#eef3ef] text-[#66746c]'];
    $penangananStatus = fn (?object $kasus): array => $kasus === null
        ? ['Belum Ditugaskan', 'bg-[#eef3ef] text-[#66746c]']
        : ($penangananLabels[$kasus->current_status] ?? [\Illuminate\Support\Str::headline((string) $kasus->current_status), 'bg-[#eef3ef] text-[#66746c]']);
    $hasFilter = $statusFilter !== '' || $tanggalDari !== '' || $tanggalSampai !== '';
@endphp

@section('content')
    <x-page-header
        title="Permohonan Saya"
        subtitle="Pantau permohonan penanganan yang Anda ajukan beserta statusnya."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Permohonan Saya'],
        ]"
    />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" x-data="{ submitting: false }">
        <form method="GET" action="{{ route('permohonan.index') }}" @submit="submitting = true"
              class="flex flex-wrap items-end gap-2">
            <div>
                <label for="filter-status" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Status</label>
                <select name="status" id="filter-status"
                        class="rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
                    <option value="">Semua Status</option>
                    @foreach ($statusLabels as $value => [$label])
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-dari" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal Dari</label>
                <input type="date" name="created_from" id="filter-dari" value="{{ $tanggalDari }}"
                       class="rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
            </div>
            <div>
                <label for="filter-sampai" class="mb-1 block text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal Sampai</label>
                <input type="date" name="created_to" id="filter-sampai" value="{{ $tanggalSampai }}"
                       class="rounded-xl border border-[#dbe5df] bg-white px-3 py-2.5 text-sm text-[#173b29] focus:border-[#176b45] focus:outline-none focus:ring-2 focus:ring-[#176b45]/20">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Terapkan</button>
            <a href="{{ route('permohonan.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Reset</a>
        </form>

        <a href="{{ route('permohonan.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4" /></svg>
            Ajukan Permohonan
        </a>

        <div x-show="submitting" x-cloak
             class="pointer-events-none fixed inset-0 z-[90] flex items-center justify-center bg-[#f7faf8]/60">
            <span class="flex items-center gap-3 rounded-full bg-white px-5 py-3 shadow-lg">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-[#e4ece7] border-t-[#176b45]"></span>
                <span class="text-sm font-semibold text-[#66746c]">Memuat…</span>
            </span>
        </div>
    </div>

    @if ($komoditasError)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div>
                <p class="font-semibold">Referensi komoditas sedang tidak dapat dimuat.</p>
                <p class="mt-0.5 text-amber-600">Nama komoditas ditampilkan sementara sebagai "#id". Data permohonan Anda tetap utuh.</p>
            </div>
        </div>
    @endif

    @if ($permohonan->isEmpty())
        <x-card class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#e8f4ed] text-[#176b45]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </span>
            <h2 class="mt-4 text-lg font-bold text-[#173b29]">{{ $hasFilter ? 'Tidak ada permohonan yang cocok' : 'Belum ada permohonan' }}</h2>
            <p class="mt-1 max-w-md text-sm text-[#66746c]">
                @if ($hasFilter)
                    Coba ubah status atau rentang tanggal, atau reset filter.
                @else
                    Jalankan diagnosis terlebih dahulu, lalu ajukan penanganan dari halaman hasilnya.
                @endif
            </p>
            @if ($hasFilter)
                <a href="{{ route('permohonan.index') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">Reset Filter</a>
            @else
                <a href="{{ route('diagnosis.index') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Diagnosis Sekarang</a>
            @endif
        </x-card>
    @else
        <x-card class="hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead>
                        <tr class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">
                            <th class="px-5 py-3">Kode Permohonan/Kasus</th>
                            <th class="px-3 py-3">Tanggal Pengajuan</th>
                            <th class="px-3 py-3">Komoditas</th>
                            <th class="px-3 py-3">Diagnosis</th>
                            <th class="px-3 py-3">Status Permohonan</th>
                            <th class="px-3 py-3">Status Penanganan</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eef3ef]">
                        @foreach ($permohonan as $item)
                            @php
                                $primary = $item->diagnosis?->results?->first();
                                [$statusLabel, $statusClass] = $status((string) $item->status);
                                [$penangananLabel, $penangananClass] = $penangananStatus($item->kasus);
                            @endphp
                            <tr class="transition-colors hover:bg-[#fafcfb]">
                                <td class="px-5 py-4">
                                    <span class="font-bold text-[#176b45]">{{ $item->permohonan_code }}</span>
                                    @if ($item->kasus !== null)
                                        <span class="block text-xs text-[#8a9990]">{{ $item->kasus->kasus_code }}</span>
                                    @else
                                        <span class="block text-xs text-[#8a9990]">#{{ $item->id }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-[#66746c]">{{ $item->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y') ?? '—' }}</td>
                                <td class="px-3 py-4 font-medium text-[#173b29]">{{ $komoditasNama((int) ($item->diagnosis?->commodity_id ?? 0)) }}</td>
                                <td class="px-3 py-4">
                                    @if ($item->diagnosis === null)
                                        <span class="text-[#8a9990]">—</span>
                                    @else
                                        <span class="font-semibold text-[#173b29]">{{ $primary?->disease_name_snapshot ?? 'Tidak ada hasil' }}</span>
                                        <span class="block text-xs text-[#8a9990]">{{ $item->diagnosis->kode }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $penangananClass }}">{{ $penangananLabel }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('permohonan.show', $item->id) }}"
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

        <div class="grid grid-cols-1 gap-4 md:hidden">
            @foreach ($permohonan as $item)
                @php
                    $primary = $item->diagnosis?->results?->first();
                    [$statusLabel, $statusClass] = $status((string) $item->status);
                    [$penangananLabel, $penangananClass] = $penangananStatus($item->kasus);
                @endphp
                <x-card class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-[#176b45]">{{ $item->permohonan_code }}</p>
                            @if ($item->kasus !== null)
                                <p class="text-xs text-[#8a9990]">{{ $item->kasus->kasus_code }}</p>
                            @endif
                        </div>
                        <p class="text-right text-sm font-semibold text-[#173b29]">{{ $komoditasNama((int) ($item->diagnosis?->commodity_id ?? 0)) }}</p>
                    </div>
                    <p class="mt-3 text-sm text-[#66746c]">
                        @if ($item->diagnosis === null)
                            —
                        @else
                            {{ $primary?->disease_name_snapshot ?? 'Tidak ada hasil' }}
                            <span class="text-xs text-[#8a9990]">({{ $item->diagnosis->kode }})</span>
                        @endif
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                            Permohonan: {{ $statusLabel }}
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $penangananClass }}">
                            Penanganan: {{ $penangananLabel }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-[#8a9990]">{{ $item->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') ?? '—' }}</p>
                    <a href="{{ route('permohonan.show', $item->id) }}"
                       class="mt-3 inline-flex items-center gap-1 rounded-lg border border-[#dbe5df] bg-white px-3 py-1.5 text-xs font-semibold text-[#176b45] hover:bg-[#f3f8f4]">
                        Detail
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-medium text-[#8a9990]">
                Menampilkan {{ $permohonan->firstItem() ?? 0 }}–{{ $permohonan->lastItem() ?? 0 }} dari {{ $permohonan->total() }} permohonan
            </p>
            <div>{{ $permohonan->links() }}</div>
        </div>
    @endif
@endsection