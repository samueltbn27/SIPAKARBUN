@extends('layouts.app')

@section('title', 'Hasil Diagnosis')

@php
    $primary = $diagnosis->results->first();
    $komoditasNama = $komoditas['nama'] ?? ('Komoditas #'.$diagnosis->commodity_id);
    $confidenceLevels = [0.2 => 'Tidak Yakin', 0.4 => 'Kurang Yakin', 0.6 => 'Cukup Yakin', 0.8 => 'Yakin', 1.0 => 'Sangat Yakin'];
    $labelCf = fn (float $v): string => $confidenceLevels[$v] ?? (round(max(0.0, $v) * 100, 1).'%');
    $createdAt = $diagnosis->created_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') ?? '—';
    $statusLabel = $diagnosis->status === 'selesai' ? 'Selesai' : \Illuminate\Support\Str::headline($diagnosis->status);
    $bolehAjukan = $diagnosis->status === \App\Models\Diagnosis::STATUS_SELESAI;
@endphp

@section('content')
    <x-page-header
        title="Hasil Diagnosis"
        subtitle="Ringkasan hasil perhitungan Certainty Factor untuk {{ $diagnosis->kode }}."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Riwayat Diagnosis', 'url' => route('diagnosis.history')],
            ['label' => $diagnosis->kode],
        ]"
    />

    <div x-data="{ ready: false }" x-init="setTimeout(() => ready = true, 400)">
        {{-- Loading state (skeleton) --}}
        <div x-show="!ready" class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" role="status" aria-busy="true">
            <p class="sr-only">Memuat hasil diagnosis…</p>
            <x-card class="p-4">
                <div class="h-3 w-20 animate-pulse rounded-md bg-[#eef3ef]"></div>
                <div class="mt-3 h-3 w-24 animate-pulse rounded-md bg-[#eef3ef]"></div>
            </x-card>
            <x-card class="p-4">
                <div class="h-3 w-20 animate-pulse rounded-md bg-[#eef3ef]"></div>
                <div class="mt-3 h-3 w-28 animate-pulse rounded-md bg-[#eef3ef]"></div>
            </x-card>
            <x-card class="p-4">
                <div class="h-3 w-20 animate-pulse rounded-md bg-[#eef3ef]"></div>
                <div class="mt-3 h-3 w-24 animate-pulse rounded-md bg-[#eef3ef]"></div>
            </x-card>
            <x-card class="p-4">
                <div class="h-3 w-20 animate-pulse rounded-md bg-[#eef3ef]"></div>
                <div class="mt-3 h-3 w-24 animate-pulse rounded-md bg-[#eef3ef]"></div>
            </x-card>
        </div>

        <div x-show="ready" x-cloak>
            @if ($komoditasError)
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <div>
                        <p class="font-semibold">Referensi komoditas sedang tidak dapat dimuat.</p>
                        <p class="mt-0.5 text-amber-600">Nama komoditas ditampilkan sementara sebagai "#id". Hasil diagnosis Anda tetap utuh.</p>
                    </div>
                </div>
            @endif

    {{-- Meta ringkasan --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Kode Diagnosis</p>
            <p class="mt-1 text-sm font-bold text-[#176b45]">{{ $diagnosis->kode }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Komoditas</p>
            <p class="mt-1 text-sm font-bold text-[#173b29]">{{ $komoditasNama }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal Diagnosis</p>
            <p class="mt-1 text-sm font-bold text-[#173b29]">{{ $createdAt }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Status</p>
            <p class="mt-1">
                <span class="rounded-full bg-[#e8f4ed] px-3 py-1 text-xs font-semibold text-[#176b45]">{{ $statusLabel }}</span>
            </p>
        </x-card>
    </div>

    @if ($primary === null)
        <x-card class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </span>
            <h2 class="mt-4 text-lg font-bold text-[#173b29]">Tidak ada penyakit yang cocok</h2>
            <p class="mt-1 max-w-md text-sm text-[#66746c]">Gejala yang dipilih tidak cukup untuk mendeteksi penyakit. Coba lakukan diagnosis ulang dengan gejala lain.</p>
            <a href="{{ route('diagnosis.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">Diagnosis Ulang</a>
            <a href="{{ route('diagnosis.history') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
        </x-card>
    @else
        {{-- Diagnosis utama --}}
        <x-card class="mb-6 overflow-hidden">
            <div class="border-b border-[#eef3ef] bg-[#fafcfb] px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-[#176b45]">Diagnosis Utama</p>
            </div>
            <div class="p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#e8f4ed] text-[#176b45]">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-extrabold tracking-tight text-[#173b29]">{{ $primary->disease_name_snapshot }}</h2>
                            <p class="mt-0.5 text-xs font-semibold text-[#8a9990]">Peringkat #1 dari {{ $diagnosis->results->count() }} kandidat</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Nilai CF</p>
                        <p class="text-3xl font-extrabold tracking-tight text-[#176b45]">{{ number_format((float) $primary->cf_value, 2, ',', '.') }}</p>
                        <p class="text-xs font-semibold text-[#8a9990]">{{ round(max(0.0, (float) $primary->cf_value) * 100, 2) }}% keyakinan</p>
                    </div>
                </div>
                <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-[#eef3ef]">
                    <div class="h-full rounded-full bg-[#176b45]" style="width: {{ min(100, round(max(0.0, (float) $primary->cf_value) * 100, 2)) }}%"></div>
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            {{-- Kiri: gejala terpilih + CF pakar --}}
            <div class="space-y-6 lg:col-span-3">
                <x-card>
                    <div class="border-b border-[#eef3ef] px-5 py-4">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Gejala Dipilih &amp; Tingkat Keyakinan</h3>
                    </div>
                    <ul class="divide-y divide-[#eef3ef]">
                        @forelse ($diagnosis->symptoms as $symptom)
                            <li class="flex flex-col gap-1 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                                <span class="flex items-center gap-2 text-sm text-[#173b29]">
                                    <svg class="h-4 w-4 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    {{ $symptom->symptom_name_snapshot }}
                                </span>
                                <span class="rounded-full bg-[#e8f4ed] px-2.5 py-1 text-xs font-semibold text-[#176b45] sm:text-right">
                                    {{ $labelCf((float) $symptom->cf_user) }}
                                    <span class="font-normal text-[#8a9990]">· {{ number_format((float) $symptom->cf_user, 1, ',', '.') }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-[#8a9990]">Tidak ada gejala terekam.</li>
                        @endforelse
                    </ul>
                </x-card>

                {{-- CF pakar (trace diagnosis utama) --}}
                @if (count($primary->trace_snapshot ?? []) > 0)
                    <x-card>
                        <div class="border-b border-[#eef3ef] px-5 py-4">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Rincian CF Pakar — {{ $primary->disease_name_snapshot }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead>
                                    <tr class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">
                                        <th class="px-5 py-3">Gejala</th>
                                        <th class="px-3 py-3 text-right">CF User</th>
                                        <th class="px-3 py-3 text-right">CF Pakar</th>
                                        <th class="px-5 py-3 text-right">CF Gejala</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#eef3ef]">
                                    @foreach ($primary->trace_snapshot as $trace)
                                        <tr>
                                            <td class="px-5 py-3 font-medium text-[#173b29]">{{ $trace['gejala_nama'] ?? 'Gejala #'.$trace['gejala_id'] }}</td>
                                            <td class="px-3 py-3 text-right text-[#66746c]">{{ number_format((float) $trace['cf_user'], 2, ',', '.') }}</td>
                                            <td class="px-3 py-3 text-right">
                                                <span class="rounded bg-[#eef3ef] px-2 py-0.5 text-xs font-semibold text-[#176b45]">{{ number_format((float) $trace['cf_pakar'], 2, ',', '.') }}</span>
                                            </td>
                                            <td class="px-5 py-3 text-right font-semibold text-[#173b29]">{{ number_format((float) $trace['cf_gejala'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="px-5 py-3 text-xs text-[#8a9990]">CF Gejala = CF User × CF Pakar. Diperlihatkan untuk diagnosis utama.</p>
                    </x-card>
                @endif

                {{-- Info snapshot/versioning --}}
                <x-card class="flex items-start gap-3 p-4">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Informasi Snapshot</p>
                        <p class="mt-1 text-xs leading-relaxed text-[#66746c]">
                            Nama gejala, hasil, nilai CF, dan solusi di halaman ini adalah <span class="font-semibold text-[#173b29]">snapshot yang disimpan saat diagnosis dijalankan</span>.
                            Riwayat tetap akurat meskipun data pengetahuan berubah di kemudian hari.
                        </p>
                    </div>
                </x-card>
            </div>

            {{-- Kanan: ranking + rekomendasi --}}
            <div class="space-y-6 lg:col-span-2">
                <x-card>
                    <div class="border-b border-[#eef3ef] px-5 py-4">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Ranking Kandidat</h3>
                    </div>
                    <ul class="divide-y divide-[#eef3ef]">
                        @foreach ($diagnosis->results as $result)
                            <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $loop->first ? 'bg-[#176b45] text-white' : 'bg-[#eef3ef] text-[#8a9990]' }}">{{ $result->ranking }}</span>
                                    <span class="text-sm font-semibold {{ $loop->first ? 'text-[#173b29]' : 'text-[#66746c]' }}">{{ $result->disease_name_snapshot }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold {{ $loop->first ? 'text-[#176b45]' : 'text-[#173b29]' }}">{{ number_format((float) $result->cf_value, 2, ',', '.') }}</span>
                                    <span class="block text-[10px] text-[#8a9990]">{{ round(max(0.0, (float) $result->cf_value) * 100, 2) }}%</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-card>

                <x-card>
                    <div class="border-b border-[#eef3ef] px-5 py-4">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Rekomendasi</h3>
                    </div>
                    <div class="p-5">
                        @forelse ($primary->solution_snapshot ?? [] as $solution)
                            <div class="mb-4 last:mb-0">
                                <h4 class="text-sm font-bold text-[#173b29]">{{ $solution['judul'] ?? 'Rekomendasi' }}</h4>
                                @if (! empty($solution['deskripsi']))
                                    <p class="mt-1 text-sm leading-relaxed text-[#66746c]">{{ $solution['deskripsi'] }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-[#8a9990]">Tidak ada rekomendasi untuk penyakit ini.</p>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            @if ($bolehAjukan)
                <a href="{{ route('permohonan.create', ['diagnosis_id' => $diagnosis->id]) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-[#176b45] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#173b29]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    Ajukan Penanganan
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-[#eef3ef] px-5 py-2.5 text-sm font-semibold text-[#8a9990]"
                      title="Ajukan Penanganan tersedia pada tahap berikutnya.">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    Ajukan Penanganan
                </span>
            @endif
            <a href="{{ route('diagnosis.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4" /></svg>
                Diagnosis Baru
            </a>
            <a href="{{ route('diagnosis.history') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
                Kembali
            </a>
        </div>
    @endif
        </div>
    </div>
@endsection