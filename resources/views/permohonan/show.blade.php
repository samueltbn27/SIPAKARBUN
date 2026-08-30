@extends('layouts.app')

@section('title', 'Detail Permohonan')

@php
    use Illuminate\Support\Facades\Storage;

    $statusLabels = [
        'diajukan' => ['Diajukan', 'bg-blue-50 text-blue-700'],
        'sedang_direview' => ['Sedang Direview', 'bg-amber-50 text-amber-700'],
        'diterima' => ['Diterima', 'bg-[#e8f4ed] text-[#176b45]'],
        'ditolak' => ['Ditolak', 'bg-red-50 text-red-700'],
    ];
    [$statusLabel, $statusClass] = $statusLabels[$permohonan->status] ?? [
        \Illuminate\Support\Str::headline((string) $permohonan->status),
        'bg-[#eef3ef] text-[#66746c]',
    ];

    $penangananLabels = [
        'diterima' => ['Diterima — Menunggu Penugasan', 'bg-[#eef3ef] text-[#66746c]'],
        'ditugaskan' => ['POPT Ditugaskan', 'bg-blue-50 text-blue-700'],
        'sedang_direview' => ['Sedang Direview', 'bg-amber-50 text-amber-700'],
        'ditunda' => ['Ditunda', 'bg-orange-50 text-orange-700'],
        'siap_dieksekusi' => ['Siap Dieksekusi', 'bg-[#e8f4ed] text-[#176b45]'],
        'dalam_pelaksanaan' => ['Dalam Pelaksanaan', 'bg-[#176b45] text-white'],
        'selesai' => ['Selesai', 'bg-[#173b29] text-white'],
    ];
    [$penangananLabel, $penangananClass] = $kasus === null
        ? ['Belum Ada Kasus', 'bg-[#eef3ef] text-[#8a9990]']
        : ($penangananLabels[$kasus->current_status] ?? [
            \Illuminate\Support\Str::headline((string) $kasus->current_status),
            'bg-[#eef3ef] text-[#66746c]',
        ]);

    $roleLabels = [
        'admin' => 'Admin Sistem',
        'operator_uptd' => 'Operator UPTD',
        'popt' => 'POPT',
        'poktan' => 'Poktan',
    ];
    $roleLabel = fn (?string $role): string => $role === null || $role === ''
        ? ''
        : ($roleLabels[$role] ?? ucfirst($role));

    $primary = $permohonan->diagnosis?->results?->first();
    $komoditasNama = $komoditas['nama'] ?? ($permohonan->diagnosis === null ? null : 'Komoditas #'.$permohonan->diagnosis->commodity_id);
    $waktu = $permohonan->created_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') ?? '—';
    $penugasanStatus = match ($penugasan?->status) {
        'aktif' => ['Penugasan Aktif', 'bg-blue-50 text-blue-700'],
        'selesai' => ['Penugasan Selesai', 'bg-[#e8f4ed] text-[#176b45]'],
        'dicabut' => ['Penugasan Dicabut', 'bg-[#eef3ef] text-[#66746c]'],
        default => ['Riwayat Penugasan', 'bg-[#eef3ef] text-[#66746c]'],
    };
@endphp

@section('content')
    <x-page-header
        title="Detail Permohonan"
        subtitle="Ringkasan permohonan penanganan {{ $permohonan->permohonan_code }} — status pantauan untuk Poktan."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Permohonan Saya', 'url' => route('permohonan.index')],
            ['label' => $permohonan->permohonan_code],
        ]"
    />

    {{-- Meta ringkasan --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">No. Permohonan</p>
            <p class="mt-1 text-sm font-bold text-[#176b45]">{{ $permohonan->permohonan_code }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Status Permohonan</p>
            <p class="mt-1"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span></p>
        </x-card>
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal Pengajuan</p>
            <p class="mt-1 text-sm font-bold text-[#173b29]">{{ $waktu }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Diagnosis</p>
            <p class="mt-1">
                @if ($permohonan->diagnosis === null)
                    <span class="text-sm text-[#8a9990]">—</span>
                @else
                    <a href="{{ route('diagnosis.show', $permohonan->diagnosis->id) }}"
                       class="text-sm font-bold text-[#176b45] hover:underline">{{ $permohonan->diagnosis->kode }}</a>
                @endif
            </p>
        </x-card>
    </div>

    {{-- BAGIAN 2 — Status Permohonan & Status Penanganan (terpisah) --}}
    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Status</h3>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <div class="border-b border-[#eef3ef] px-5 py-4">
                <h4 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Status Permohonan</h4>
            </div>
            <div class="p-5">
                <p><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span></p>

                @if ($permohonan->status === 'sedang_direview')
                    <p class="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-700">
                        Sedang diperiksa oleh Operator UPTD.
                        @if ($permohonan->reviewed_at !== null)
                            Sejak {{ $permohonan->reviewed_at->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') }}.
                        @endif
                    </p>
                @endif

                @if ($permohonan->keputusan !== null)
                    <p class="mt-3 rounded-xl bg-[#f3f8f4] p-3 text-sm leading-relaxed text-[#66746c]">
                        {{ $permohonan->keputusan->catatan !== null && $permohonan->keputusan->catatan !== ''
                            ? $permohonan->keputusan->catatan
                            : 'Keputusan operator atas permohonan ini.' }}
                    </p>
                    <p class="mt-3 text-xs text-[#8a9990]">
                        Diputuskan {{ $permohonan->keputusan->decided_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') ?? '—' }}
                        @if ($permohonan->keputusan->operator !== null)
                            oleh {{
                                trim(
                                    $permohonan->keputusan->operator->name
                                    .' ('.($roleLabel($permohonan->keputusan->operator->roles->first()?->name)).')'
                                )
                            }}
                            .
                        @endif
                    </p>
                @endif
            </div>
        </x-card>

        <x-card>
            <div class="border-b border-[#eef3ef] px-5 py-4">
                <h4 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Status Penanganan</h4>
            </div>
            <div class="p-5">
                @if ($kasus === null)
                    <p class="text-sm text-[#66746c]">Belum ada kasus penanganan.</p>
                    <p class="mt-2 text-xs text-[#8a9990]">
                        Status penanganan muncul setelah permohonan diterima oleh Operator UPTD dan dijadikan kasus.
                    </p>
                @else
                    <p class="flex items-center gap-2">
                        <span class="text-sm font-bold text-[#173b29]">{{ $kasus->kasus_code }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $penangananClass }}">{{ $penangananLabel }}</span>
                    </p>
                    <p class="mt-2 text-xs text-[#8a9990]">
                        Kasus dibuat {{ $kasus->created_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') ?? '—' }}
                        ketika permohonan diterima.
                    </p>
                @endif
            </div>
        </x-card>
    </div>

    {{-- BAGIAN 3 — POPT (read only) --}}
    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">POPT</h3>
    <x-card>
        @if ($penugasan !== null && $penugasan->popt !== null)
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e8f4ed] text-sm font-bold text-[#176b45]">
                        {{ strtoupper(mb_substr($penugasan->popt->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#173b29]">{{ $penugasan->popt->name }}</p>
                        <p class="mt-0.5 flex items-center gap-2 text-xs text-[#8a9990]">
                            <span class="rounded bg-[#eef3ef] px-1.5 py-0.5 font-semibold text-[#66746c]">POPT</span>
                            Ditugaskan {{ $penugasan->assigned_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') ?? '—' }}
                        </p>
                    </div>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $penugasanStatus[1] }}">
                    {{ $penugasanStatus[0] }}
                </span>
            </div>
            @if ($penugasan->catatan !== null && $penugasan->catatan !== '')
                <p class="mx-5 mb-5 rounded-xl bg-[#f3f8f4] p-3 text-sm leading-relaxed text-[#66746c]">
                    <span class="font-semibold text-[#173b29]">Catatan penugasan:</span>
                    {{ $penugasan->catatan }}
                </p>
            @endif
        @else
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eef3ef] text-[#a0aba4]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </span>
                <p class="mt-4 text-sm font-semibold text-[#66746c]">Belum ada petugas yang ditugaskan.</p>
                <p class="mt-1 max-w-sm text-xs text-[#8a9990]">
                    Status akan berubah setelah Operator UPTD menugaskan POPT ke kasus Anda.
                </p>
            </div>
        @endif
    </x-card>

    {{-- BAGIAN 1 — Informasi Permohonan --}}
    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Informasi Permohonan</h3>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {{-- Kolom kiri: lokasi & data permohonan --}}
        <div class="space-y-6 lg:col-span-3">
            <x-card>
                <div class="border-b border-[#eef3ef] px-5 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Lokasi Kasus</h3>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Latitude</p>
                            <p class="mt-1 text-sm font-bold text-[#173b29]">{{ $permohonan->latitude_kasus !== null ? number_format((float) $permohonan->latitude_kasus, 7, ',', '.') : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Longitude</p>
                            <p class="mt-1 text-sm font-bold text-[#173b29]">{{ $permohonan->longitude_kasus !== null ? number_format((float) $permohonan->longitude_kasus, 7, ',', '.') : '—' }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Alamat / Keterangan</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-[#66746c]">{{ $permohonan->alamat_kasus ?? '—' }}</p>
                    </div>
                    <p class="mt-4 rounded-xl bg-[#f3f8f4] p-3 text-xs text-[#66746c]">
                        Lokasi kasus adalah titik serangan OPT di lapangan dan <span class="font-semibold text-[#173b29]">berbeda dari lokasi kelompok tani</span>.
                    </p>
                </div>
            </x-card>

            <x-card>
                <div class="border-b border-[#eef3ef] px-5 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Kelompok Tani</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm font-bold text-[#173b29]">{{ $permohonan->kelompok_tani_name_snapshot }}</p>
                    <p class="mt-0.5 text-xs text-[#8a9990]">ID Kelompok Tani (Shared Integration): #{{ $permohonan->kelompok_tani_id }}</p>
                    <p class="mt-3 text-xs text-[#8a9990]">Lokasi administratif kelompok tani tidak otomatis dipakai sebagai lokasi kasus.</p>
                </div>
            </x-card>

            <x-card>
                <div class="border-b border-[#eef3ef] px-5 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Catatan Pemohon</h3>
                </div>
                <div class="p-5">
                    <p class="whitespace-pre-line text-sm text-[#66746c]">{{ $permohonan->catatan_pemohon ?? '—' }}</p>
                </div>
            </x-card>

            <x-card>
                <div class="border-b border-[#eef3ef] px-5 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Foto / Bukti ({{ $permohonan->evidences->count() }})</h3>
                </div>
                <div class="p-5">
                    @forelse ($permohonan->evidences as $evidence)
                        <a href="{{ Storage::disk('public')->url($evidence->file_path) }}" target="_blank"
                           class="group mb-3 flex items-center gap-3 rounded-xl border border-[#eef3ef] p-3 transition-colors hover:border-[#176b45]/40 hover:bg-[#fafcfb] last:mb-0">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#e8f4ed] text-[#176b45]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-[#173b29] group-hover:text-[#176b45]">{{ $evidence->file_name }}</span>
                                <span class="text-xs text-[#8a9990]">{{ $evidence->mime_type }}</span>
                            </span>
                            <svg class="ml-auto h-4 w-4 shrink-0 text-[#b9c4bd]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6v6m-11 5L20 4" /></svg>
                        </a>
                    @empty
                        <p class="text-sm text-[#8a9990]">Tidak ada file bukti.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Kolom kanan: diagnosis pendukung --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <div class="border-b border-[#eef3ef] px-5 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Diagnosis Pendukung</h3>
                </div>
                @if ($permohonan->diagnosis === null)
                    <div class="p-5 text-sm text-[#8a9990]">Data diagnosis tidak tersedia.</div>
                @else
                    <dl class="divide-y divide-[#eef3ef] text-sm">
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <dt class="text-[#8a9990]">Komoditas</dt>
                            <dd class="text-right font-semibold text-[#173b29]">{{ $komoditasNama ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <dt class="text-[#8a9990]">Penyakit Utama</dt>
                            <dd class="text-right font-semibold text-[#173b29]">{{ $primary?->disease_name_snapshot ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <dt class="text-[#8a9990]">Nilai CF</dt>
                            <dd class="font-bold text-[#176b45]">{{ $primary === null ? '—' : number_format((float) $primary->cf_value, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                    <div class="px-5 py-3">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Gejala Dipilih</p>
                        <ul class="space-y-1">
                            @forelse ($permohonan->diagnosis->symptoms as $symptom)
                                <li class="flex items-center gap-2 text-xs text-[#66746c]">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-[#176b45]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    {{ $symptom->symptom_name_snapshot }}
                                </li>
                            @empty
                                <li class="text-xs text-[#8a9990]">—</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="px-5 py-4">
                        <a href="{{ route('diagnosis.show', $permohonan->diagnosis->id) }}"
                           class="text-xs font-semibold text-[#176b45] hover:underline">Lihat detail diagnosis →</a>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- BAGIAN 4 — Timeline --}}
    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Timeline</h3>
    <x-card>
        @if (count($timeline) === 0)
            <div class="p-5 text-sm text-[#8a9990]">Belum ada aktivitas untuk permohonan ini.</div>
        @else
            <ol class="px-5 py-5">
                @foreach ($timeline as $i => $item)
                    @php
                        $isLast = $i === array_key_last($timeline);
                        $waktuText = $item['waktu']?->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') ?? '—';
                        $actorText = trim(
                            ($item['actor'] ?? '')
                            .($item['actor_role'] ? ' ('.$roleLabel($item['actor_role']).')' : '')
                        );
                    @endphp
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        {{-- Jalur vertikal --}}
                        @if (! $isLast)
                            <span class="absolute left-[11px] top-6 h-full w-0.5 bg-[#e4ece7]"></span>
                        @endif

                        {{-- Indikator: riwayat tuntas ✓, status terakhir ● --}}
                        <span class="relative z-10 mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full
                             @if ($isLast) bg-[#176b45] text-white
                             @else bg-[#e8f4ed] text-[#176b45] @endif">
                            @if ($isLast)
                                <span class="h-2.5 w-2.5 rounded-full bg-white"></span>
                            @else
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-bold text-[#173b29]">{{ $item['label'] }}</p>
                                <p class="text-xs text-[#8a9990]">{{ $waktuText }}</p>
                            </div>
                            @if ($actorText !== '')
                                <p class="mt-0.5 text-xs text-[#8a9990]">oleh {{ $actorText }}</p>
                            @endif
                            @if ($item['catatan'] !== null && $item['catatan'] !== '')
                                <p class="mt-2 rounded-lg bg-[#fafcfb] p-2.5 text-xs leading-relaxed text-[#66746c]">{{ $item['catatan'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-card>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('permohonan.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Daftar
        </a>
    </div>
@endsection
