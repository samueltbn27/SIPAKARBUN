@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $roleLabels = [
            'admin' => 'Admin Sistem',
            'operator_uptd' => 'Operator UPTD',
            'popt' => 'POPT',
            'poktan' => 'Poktan',
        ];
        $roleLabel = $roleLabels[$role] ?? 'Pengguna';

        $quickRoutes = [
            'poktan' => [
                ['route' => 'diagnosis.index', 'label' => 'Diagnosis Baru', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['route' => 'diagnosis.history', 'label' => 'Riwayat Diagnosis', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['route' => 'permohonan.index', 'label' => 'Permohonan Saya', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ],
            'operator_uptd' => [
                ['route' => 'operator.permohonan', 'label' => 'Permohonan Masuk', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                ['route' => 'kasus.index', 'label' => 'Kasus', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
            ],
            'popt' => [
                ['route' => 'popt.penugasan', 'label' => 'Penugasan Saya', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ],
            'admin' => [
                ['route' => 'operator.permohonan', 'label' => 'Permohonan Masuk', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                ['route' => 'kasus.index', 'label' => 'Kasus', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
                ['route' => 'pengguna.index', 'label' => 'Pengguna', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z'],
            ],
        ];
        $quick = $quickRoutes[$role] ?? [];
    @endphp

    <x-card class="mb-6 overflow-hidden">
        <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-[#176b45]">Sistem Pakar &amp; Penanganan Kasus</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-[#173b29] sm:text-2xl">Selamat datang, {{ $user->name }}</h2>
                <p class="mt-1 text-sm text-[#66746c]">Anda masuk sebagai <span class="font-semibold text-[#176b45]">{{ $roleLabel }}</span> · {{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="shrink-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-[#e8f4ed] px-4 py-2 text-sm font-semibold text-[#176b45]">
                    <span class="h-2 w-2 rounded-full bg-[#176b45]"></span>
                    {{ $roleLabel }}
                </span>
            </div>
        </div>
    </x-card>

    {{-- Ringkasan statistik — loading state (skeleton) lalu isi nyata --}}
    <div x-data="{ ready: false }" x-init="setTimeout(() => ready = true, 500)">
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Ringkasan</h3>

        <div x-show="!ready" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($stats as $s)
                <div class="soft-card animate-pulse rounded-2xl border border-[#e4ece7] bg-white p-6">
                    <div class="h-10 w-10 rounded-xl bg-[#eef3ef]"></div>
                    <div class="mt-4 h-7 w-16 rounded-md bg-[#eef3ef]"></div>
                    <div class="mt-2 h-3 w-32 rounded-md bg-[#eef3ef]"></div>
                </div>
            @endforeach
        </div>

        <div x-show="ready" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($stats as $s)
                <x-card class="p-6">
                    <div class="flex items-start justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f4ed] text-[#176b45]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
                        </span>
                        <span class="text-3xl font-extrabold tracking-tight text-[#173b29]">{{ number_format($s['value'], 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-3 text-sm font-semibold text-[#66746c]">{{ $s['label'] }}</div>
                </x-card>
            @endforeach
        </div>
    </div>

    {{-- Aktivitas terakhir — khusus role poktan (diagnosis & permohonan terbaru) --}}
    @if ($role === 'poktan' && ! empty($recents))
        @php
            $statusLabels = [
                'diajukan' => ['Diajukan', 'bg-blue-50 text-blue-700'],
                'sedang_direview' => ['Sedang Direview', 'bg-amber-50 text-amber-700'],
                'diterima' => ['Diterima', 'bg-[#e8f4ed] text-[#176b45]'],
                'ditolak' => ['Ditolak', 'bg-red-50 text-red-700'],
            ];
            $penangananLabels = [
                'diterima' => ['Menunggu Penugasan', 'bg-blue-50 text-blue-700'],
                'ditugaskan' => ['POPT Ditugaskan', 'bg-indigo-50 text-indigo-700'],
                'sedang_direview' => ['Sedang Direview', 'bg-amber-50 text-amber-700'],
                'ditunda' => ['Ditunda', 'bg-slate-100 text-slate-600'],
                'siap_dieksekusi' => ['Siap Dieksekusi', 'bg-sky-50 text-sky-700'],
                'dalam_pelaksanaan' => ['Dalam Pelaksanaan', 'bg-purple-50 text-purple-700'],
                'selesai' => ['Selesai', 'bg-[#e8f4ed] text-[#176b45]'],
            ];
            $status = fn (array $map, string $value): array => $map[$value] ?? [\Illuminate\Support\Str::headline($value), 'bg-[#eef3ef] text-[#66746c]'];
        @endphp

        <div x-data="{ ready: false }" x-init="setTimeout(() => ready = true, 500)">
            <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Aktivitas Terakhir</h3>

            {{-- Loading state (skeleton) --}}
            <div x-show="!ready" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="soft-card rounded-2xl border border-[#e4ece7] bg-white p-5">
                    <div class="h-4 w-32 animate-pulse rounded-md bg-[#eef3ef]"></div>
                    <div class="mt-4 space-y-3">
                        <div class="h-3 w-full animate-pulse rounded-md bg-[#eef3ef]"></div>
                        <div class="h-3 w-5/6 animate-pulse rounded-md bg-[#eef3ef]"></div>
                        <div class="h-3 w-2/3 animate-pulse rounded-md bg-[#eef3ef]"></div>
                    </div>
                </div>
                <div class="soft-card rounded-2xl border border-[#e4ece7] bg-white p-5">
                    <div class="h-4 w-32 animate-pulse rounded-md bg-[#eef3ef]"></div>
                    <div class="mt-4 space-y-3">
                        <div class="h-3 w-full animate-pulse rounded-md bg-[#eef3ef]"></div>
                        <div class="h-3 w-5/6 animate-pulse rounded-md bg-[#eef3ef]"></div>
                        <div class="h-3 w-2/3 animate-pulse rounded-md bg-[#eef3ef]"></div>
                    </div>
                </div>
            </div>

            <div x-show="ready" x-cloak class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {{-- Diagnosis Terbaru --}}
                <x-card class="p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Diagnosis Terbaru</h4>
                        <a href="{{ route('diagnosis.history') }}" class="text-xs font-semibold text-[#176b45] hover:underline">Lihat semua</a>
                    </div>

                    @if ($recents['komoditas_error'])
                        <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-700">
                            Nama komoditas tidak dapat dimuat — ditampilkan sementara sebagai "#id".
                        </p>
                    @endif

                    @if ($recents['recent_diagnoses']->isEmpty())
                        <div class="mt-4 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-8 text-center">
                            <p class="text-sm font-semibold text-[#66746c]">Belum ada diagnosis</p>
                            <a href="{{ route('diagnosis.index') }}" class="mt-2 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Diagnosis Sekarang</a>
                        </div>
                    @else
                        <ul class="mt-4 divide-y divide-[#eef3ef]">
                            @foreach ($recents['recent_diagnoses'] as $d)
                                @php
                                    $top = $d->results?->first();
                                    $komoditasNama = $recents['komoditas_map'][(int) $d->commodity_id] ?? ('Komoditas #'.$d->commodity_id);
                                @endphp
                                <li>
                                    <a href="{{ route('diagnosis.show', $d->id) }}" class="group flex items-center justify-between gap-3 rounded-xl px-2 py-3 transition-colors hover:bg-[#fafcfb]">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-[#176b45] group-hover:underline">{{ $d->kode }}</span>
                                            <span class="mt-0.5 block truncate text-xs text-[#66746c]">
                                                {{ $komoditasNama }}
                                                @if ($top !== null)
                                                    · {{ $top->disease_name_snapshot }}
                                                @endif
                                            </span>
                                            <span class="mt-0.5 block text-[11px] text-[#8a9990]">{{ $d->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') ?? '—' }}</span>
                                        </span>
                                        <span class="shrink-0 text-right">
                                            <span class="text-sm font-extrabold text-[#176b45]">{{ $top === null ? '—' : number_format((float) $top->cf_value, 2, ',', '.') }}</span>
                                            <span class="block text-[10px] text-[#8a9990]">CF</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                {{-- Permohonan Terbaru --}}
                <x-card class="p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Permohonan Terbaru</h4>
                        <a href="{{ route('permohonan.index') }}" class="text-xs font-semibold text-[#176b45] hover:underline">Lihat semua</a>
                    </div>

                    @if ($recents['recent_permohonan']->isEmpty())
                        <div class="mt-4 rounded-xl border border-dashed border-[#dbe5df] bg-[#fafcfb] px-4 py-8 text-center">
                            <p class="text-sm font-semibold text-[#66746c]">Belum ada permohonan</p>
                            <a href="{{ route('diagnosis.index') }}" class="mt-2 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-4 py-2 text-sm font-semibold text-white hover:bg-[#173b29]">Diagnosis Sekarang</a>
                        </div>
                    @else
                        <ul class="mt-4 divide-y divide-[#eef3ef]">
                            @foreach ($recents['recent_permohonan'] as $p)
                                @php
[$statusLabel, $statusClass] = $status($statusLabels, (string) $p->status);
                    $kasus = $p->kasus;
                    [$penangananLabel, $penangananClass] = $kasus === null
                        ? ['Belum Ada Kasus', 'bg-[#eef3ef] text-[#8a9990]']
                        : $status($penangananLabels, (string) $kasus->current_status);
                                    $komoditasNama = $recents['komoditas_map'][(int) ($p->diagnosis?->commodity_id ?? 0)] ?? ('Komoditas #'.($p->diagnosis?->commodity_id ?? 0));
                                @endphp
                                <li>
                                    <a href="{{ route('permohonan.show', $p->id) }}" class="group flex items-center justify-between gap-3 rounded-xl px-2 py-3 transition-colors hover:bg-[#fafcfb]">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-[#176b45] group-hover:underline">{{ $p->permohonan_code }}</span>
                                            <span class="mt-0.5 block truncate text-xs text-[#66746c]">{{ $komoditasNama }}</span>
                                            <span class="mt-0.5 block text-[11px] text-[#8a9990]">{{ $p->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') ?? '—' }}</span>
                                        </span>
                                        <span class="flex shrink-0 flex-col items-end gap-1">
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $penangananClass }}">{{ $penangananLabel }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        </div>
    @endif

    {{-- Akses cepat --}}
    @if (count($quick) > 0)
        <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Akses Cepat</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($quick as $q)
                <a href="{{ route($q['route']) }}"
                   class="soft-card group flex items-center gap-4 rounded-2xl border border-[#e4ece7] bg-white p-5 transition-colors hover:border-[#176b45]/30 hover:bg-[#fbfdfb]">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f4ed] text-[#176b45] transition-colors group-hover:bg-[#176b45] group-hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $q['icon'] }}" /></svg>
                    </span>
                    <span class="flex-1 text-sm font-semibold text-[#173b29]">{{ $q['label'] }}</span>
                    <svg class="h-4 w-4 text-[#b9c4bd] transition-colors group-hover:text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            @endforeach
        </div>
    @endif
@endsection