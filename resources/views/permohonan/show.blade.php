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
    $primary = $permohonan->diagnosis?->results?->first();
    $komoditasNama = $komoditas['nama'] ?? ($permohonan->diagnosis === null ? null : 'Komoditas #'.$permohonan->diagnosis->commodity_id);
    $formatDate = fn ($date, string $format = 'd M Y · H:i'): string => $date?->timezone('Asia/Jakarta')->translatedFormat($format) ?? '—';
    $roleLabels = [
        'admin' => 'Admin Sistem',
        'operator_uptd' => 'Operator UPTD',
        'popt' => 'POPT',
        'poktan' => 'Poktan',
    ];
    $roleLabel = fn (?string $role): string => $role === null || $role === ''
        ? ''
        : ($roleLabels[$role] ?? ucfirst($role));
    $waktu = $formatDate($permohonan->created_at, 'd F Y H:i');
    $isRejectedWithoutCase = $permohonan->status === 'ditolak' && $kasus === null;
@endphp

@section('content')
    <x-page-header
        title="Detail Permohonan"
        subtitle="Ringkasan permohonan penanganan {{ $permohonan->permohonan_code }} — pantau perkembangan kasus Anda."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Permohonan Saya', 'url' => route('permohonan.index')],
            ['label' => $permohonan->permohonan_code],
        ]"
    />

    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-card class="p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">No. Permohonan</p><p class="mt-1 text-sm font-bold text-[#176b45]">{{ $permohonan->permohonan_code }}</p></x-card>
        <x-card class="p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Status Permohonan</p><p class="mt-1"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span></p></x-card>
        <x-card class="p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal Pengajuan</p><p class="mt-1 text-sm font-bold text-[#173b29]">{{ $waktu }}</p></x-card>
        <x-card class="p-4"><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Diagnosis</p><p class="mt-1">@if ($permohonan->diagnosis === null)<span class="text-sm text-[#8a9990]">—</span>@else<a href="{{ route('diagnosis.show', $permohonan->diagnosis->id) }}" class="text-sm font-bold text-[#176b45] hover:underline">{{ $permohonan->diagnosis->kode }}</a>@endif</p></x-card>
    </div>

    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Status</h3>
    <div class="grid grid-cols-1 gap-6 {{ $kasus !== null || ! $isRejectedWithoutCase ? 'lg:grid-cols-2' : '' }}">
        <x-card>
            <div class="border-b border-[#eef3ef] px-5 py-4"><h4 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Status Permohonan</h4></div>
            <div class="p-5">
                <p><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span></p>
                @if ($permohonan->status === 'sedang_direview')
                    <p class="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-700">Sedang diperiksa oleh Operator UPTD.@if ($permohonan->reviewed_at !== null) Sejak {{ $formatDate($permohonan->reviewed_at) }}.@endif</p>
                @endif
                @if ($permohonan->keputusan !== null)
                    <p class="mt-3 rounded-xl bg-[#f3f8f4] p-3 text-sm leading-relaxed text-[#66746c]">{{ $permohonan->keputusan->catatan !== null && $permohonan->keputusan->catatan !== '' ? $permohonan->keputusan->catatan : 'Keputusan operator atas permohonan ini.' }}</p>
                    <p class="mt-3 text-xs text-[#8a9990]">Diputuskan {{ $formatDate($permohonan->keputusan->decided_at) }} @if ($permohonan->keputusan->operator !== null) oleh {{ trim($permohonan->keputusan->operator->name .' ('.($roleLabel($permohonan->keputusan->operator->roles->first()?->name)).')') }}.@endif</p>
                @endif
                @if ($kasus === null && ! $isRejectedWithoutCase)
                    <p class="mt-4 text-sm text-[#66746c]">Belum ada kasus penanganan.</p><p class="mt-2 text-xs text-[#8a9990]">Status penanganan muncul setelah permohonan diterima oleh Operator UPTD dan dijadikan kasus.</p><p class="mt-2 text-xs text-[#8a9990]">Belum ada petugas yang ditugaskan.</p>
                @endif
            </div>
        </x-card>

        @if ($kasus !== null)
            <x-card>
                <div class="border-b border-[#eef3ef] px-5 py-4"><h4 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Status Penanganan</h4></div>
                <div class="p-5">
                    <div class="flex flex-wrap items-center gap-2"><span class="text-sm font-bold text-[#173b29]">{{ $kasus->kasus_code }}</span><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $handling['status']['badge_class'] }}">{{ $handling['status']['label'] }}</span></div>
                    @if ($handling['status']['is_overdue'])<p class="mt-3 rounded-xl bg-red-50 p-3 text-xs text-red-700">Penanganan telah melewati target penyelesaian.</p>@else<p class="mt-3 text-xs text-[#8a9990]">Kasus dibuat {{ $formatDate($kasus->created_at) }} ketika permohonan diterima.</p>@endif
                    @if ($handling['last_update_at'] !== null)<p class="mt-3 text-xs text-[#8a9990]">Update terakhir: {{ $formatDate($handling['last_update_at']) }}</p>@endif
                </div>
            </x-card>
        @endif
    </div>

    @if ($kasus !== null)
        <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Informasi Penanganan</h3>
        <x-card>
            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Petugas POPT</p><p class="mt-1 text-sm font-bold text-[#173b29]">{{ $handling['popt']?->name ?? 'Belum ditugaskan' }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Tanggal Penugasan</p><p class="mt-1 text-sm font-semibold text-[#173b29]">{{ $formatDate($handling['assigned_at']) }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Target Penyelesaian</p><p class="mt-1 text-sm font-semibold text-[#173b29]">{{ $formatDate($handling['deadline_at']) }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Status Waktu</p><p class="mt-1 text-sm font-semibold {{ $handling['status']['is_overdue'] ? 'text-red-700' : 'text-[#176b45]' }}">{{ $handling['time_label'] }}</p></div>
            </div>
            <div class="border-t border-[#eef3ef] px-5 py-4">
                <p class="text-xs text-[#66746c]">{{ $handling['acceptance_label'] }}</p>
                @if ($handling['assignment'] !== null)
                    <p class="mt-2 text-xs text-[#8a9990]">{{ $handling['assignment_status_label'] }}</p>
                    @if ($handling['assignment']->catatan !== null && $handling['assignment']->catatan !== '')<p class="mt-3 rounded-xl bg-[#f3f8f4] p-3 text-sm leading-relaxed text-[#66746c]"><span class="font-semibold text-[#173b29]">Catatan penugasan:</span> {{ $handling['assignment']->catatan }}</p>@endif
                @endif
            </div>
        </x-card>
    @endif

    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Informasi Permohonan</h3>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            <x-card><div class="border-b border-[#eef3ef] px-5 py-4"><h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Lokasi Kasus</h3></div><div class="p-5"><div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Latitude</p><p class="mt-1 text-sm font-bold text-[#173b29]">{{ $permohonan->latitude_kasus !== null ? number_format((float) $permohonan->latitude_kasus, 7, ',', '.') : '—' }}</p></div><div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Longitude</p><p class="mt-1 text-sm font-bold text-[#173b29]">{{ $permohonan->longitude_kasus !== null ? number_format((float) $permohonan->longitude_kasus, 7, ',', '.') : '—' }}</p></div></div><div class="mt-4"><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Alamat / Keterangan</p><p class="mt-1 whitespace-pre-line text-sm text-[#66746c]">{{ $permohonan->alamat_kasus ?? '—' }}</p></div><p class="mt-4 rounded-xl bg-[#f3f8f4] p-3 text-xs text-[#66746c]">Lokasi kasus adalah titik serangan OPT di lapangan dan <span class="font-semibold text-[#173b29]">berbeda dari lokasi kelompok tani</span>.</p></div></x-card>
            <x-card><div class="border-b border-[#eef3ef] px-5 py-4"><h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Kelompok Tani</h3></div><div class="p-5"><p class="text-sm font-bold text-[#173b29]">{{ $permohonan->kelompok_tani_name_snapshot }}</p><p class="mt-0.5 text-xs text-[#8a9990]">ID Kelompok Tani (Shared Integration): #{{ $permohonan->kelompok_tani_id }}</p><p class="mt-3 text-xs text-[#8a9990]">Lokasi administratif kelompok tani tidak otomatis dipakai sebagai lokasi kasus.</p></div></x-card>
            <x-card><div class="border-b border-[#eef3ef] px-5 py-4"><h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Catatan Pemohon</h3></div><div class="p-5"><p class="whitespace-pre-line text-sm text-[#66746c]">{{ $permohonan->catatan_pemohon ?? '—' }}</p></div></x-card>
            <x-card><div class="border-b border-[#eef3ef] px-5 py-4"><h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Foto / Bukti ({{ $permohonan->evidences->count() }})</h3></div><div class="p-5">@forelse ($permohonan->evidences as $evidence)<a href="{{ Storage::disk('public')->url($evidence->file_path) }}" target="_blank" class="group mb-3 flex items-center gap-3 rounded-xl border border-[#eef3ef] p-3 transition-colors hover:border-[#176b45]/40 hover:bg-[#fafcfb] last:mb-0"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#e8f4ed] text-[#176b45]">📷</span><span class="min-w-0"><span class="block truncate text-sm font-semibold text-[#173b29] group-hover:text-[#176b45]">{{ $evidence->file_name }}</span><span class="text-xs text-[#8a9990]">{{ $evidence->mime_type }}</span></span><span class="ml-auto shrink-0 text-xs font-semibold text-[#176b45]">Buka</span></a>@empty<p class="text-sm text-[#8a9990]">Tidak ada file bukti.</p>@endforelse</div></x-card>
        </div>
        <div class="space-y-6 lg:col-span-2">
            <x-card><div class="border-b border-[#eef3ef] px-5 py-4"><h3 class="text-sm font-bold uppercase tracking-wide text-[#8a9990]">Diagnosis Pendukung</h3></div>@if ($permohonan->diagnosis === null)<div class="p-5 text-sm text-[#8a9990]">Data diagnosis tidak tersedia.</div>@else<dl class="divide-y divide-[#eef3ef] text-sm"><div class="flex items-center justify-between gap-4 px-5 py-3.5"><dt class="text-[#8a9990]">Komoditas</dt><dd class="text-right font-semibold text-[#173b29]">{{ $komoditasNama ?? '—' }}</dd></div><div class="flex items-center justify-between gap-4 px-5 py-3.5"><dt class="text-[#8a9990]">Penyakit Utama</dt><dd class="text-right font-semibold text-[#173b29]">{{ $primary?->disease_name_snapshot ?? '—' }}</dd></div><div class="flex items-center justify-between gap-4 px-5 py-3.5"><dt class="text-[#8a9990]">Nilai CF</dt><dd class="font-bold text-[#176b45]">{{ $primary === null ? '—' : number_format((float) $primary->cf_value, 2, ',', '.') }}</dd></div></dl><div class="px-5 py-3"><p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#8a9990]">Gejala Dipilih</p><ul class="space-y-1">@forelse ($permohonan->diagnosis->symptoms as $symptom)<li class="flex items-center gap-2 text-xs text-[#66746c]"><span class="text-[#176b45]">✓</span>{{ $symptom->symptom_name_snapshot }}</li>@empty<li class="text-xs text-[#8a9990]">—</li>@endforelse</ul></div><div class="px-5 py-4"><a href="{{ route('diagnosis.show', $permohonan->diagnosis->id) }}" class="text-xs font-semibold text-[#176b45] hover:underline">Lihat detail diagnosis →</a></div>@endif</x-card>
        </div>
    </div>

    @if ($kasus !== null)
        <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Perkembangan Penanganan</h3>
        <x-card>
            @if ($handling['progress_count'] === 0)
                <p class="p-5 pb-0 text-sm text-[#8a9990]">Belum ada update progress dari POPT.</p>
            @endif
            @if (count($handling['progress_timeline']) > 0)
                <ol class="px-5 py-5">
                    @foreach ($handling['progress_timeline'] as $i => $item)
                        @php $isLast = $i === array_key_last($handling['progress_timeline']); @endphp
                        <li class="relative flex gap-4 pb-6 last:pb-0">@if (! $isLast)<span class="absolute left-[11px] top-6 h-full w-0.5 bg-[#e4ece7]"></span>@endif<span class="relative z-10 mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $isLast ? 'bg-[#176b45] text-white' : 'bg-[#e8f4ed] text-[#176b45]' }}">{{ $isLast ? '•' : '✓' }}</span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold text-[#173b29]">{{ $item['label'] }}</p><p class="text-xs text-[#8a9990]">{{ $formatDate($item['waktu']) }}</p></div>@if ($item['actor'])<p class="mt-0.5 text-xs text-[#8a9990]">oleh {{ $item['actor'] }}</p>@endif @if ($item['catatan'])<p class="mt-2 whitespace-pre-line rounded-lg bg-[#fafcfb] p-2.5 text-xs leading-relaxed text-[#66746c]">{{ $item['catatan'] }}</p>@endif</div></li>
                    @endforeach
                </ol>
            @endif
        </x-card>

        @if (count($handling['extensions']) > 0)
            <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Riwayat Perpanjangan</h3>
            <div class="space-y-4">
                @foreach ($handling['extensions'] as $extension)
                    <x-card class="p-5"><div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm font-bold text-[#173b29]">Permintaan Perpanjangan</p><span class="rounded-full bg-[#eef3ef] px-3 py-1 text-xs font-semibold text-[#66746c]">{{ $extension['status_label'] }}</span></div><p class="mt-2 text-xs text-[#8a9990]">Diajukan {{ $formatDate($extension['created_at']) }}</p><dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2"><div><dt class="text-xs text-[#8a9990]">Target sebelumnya</dt><dd class="mt-1 font-semibold text-[#173b29]">{{ $formatDate($extension['current_deadline_at']) }}</dd></div><div><dt class="text-xs text-[#8a9990]">Usulan target baru</dt><dd class="mt-1 font-semibold text-[#173b29]">{{ $formatDate($extension['proposed_deadline_at']) }}</dd></div></dl><p class="mt-4 whitespace-pre-line rounded-xl bg-[#f3f8f4] p-3 text-sm leading-relaxed text-[#66746c]"><span class="font-semibold text-[#173b29]">Alasan:</span> {{ $extension['reason'] }}</p>@if ($extension['reviewed_at'])<p class="mt-3 text-xs text-[#8a9990]">Diproses {{ $formatDate($extension['reviewed_at']) }}.</p>@endif</x-card>
                @endforeach
            </div>
        @endif

        @if ($kasus->current_status === 'selesai')
            <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Hasil Penanganan</h3>
            <x-card>
                @if ($handling['final_report'] === null)
                    <p class="p-5 text-sm text-[#8a9990]">Laporan akhir belum tersedia untuk kasus historis ini.</p>
                @else
                    <div class="space-y-5 p-5"><p class="text-xs text-[#8a9990]">Dilaporkan {{ $formatDate($handling['final_report']->submitted_at) }}</p><div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Ringkasan Tindakan</p><p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#66746c]">{{ $handling['final_report']->ringkasan_tindakan }}</p></div><div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Hasil Penanganan</p><p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#66746c]">{{ $handling['final_report']->hasil_penanganan }}</p></div><div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Rekomendasi / Tindak Lanjut</p><p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#66746c]">{{ $handling['final_report']->rekomendasi }}</p></div>@if ($handling['final_report']->catatan_tambahan)<div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Catatan Tambahan</p><p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#66746c]">{{ $handling['final_report']->catatan_tambahan }}</p></div>@endif<div><p class="text-xs font-bold uppercase tracking-wide text-[#8a9990]">Dokumentasi Hasil Penanganan</p>@if (count($handling['final_evidences']) === 0)<p class="mt-2 text-sm text-[#8a9990]">Tidak ada dokumentasi foto.</p>@else<div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">@foreach ($handling['final_evidences'] as $evidence)<a href="{{ $evidence['url'] }}" target="_blank" class="group overflow-hidden rounded-xl border border-[#dbe5df] bg-[#fafcfb]"><img src="{{ $evidence['url'] }}" alt="{{ $evidence['file_name'] }}" class="aspect-square w-full object-cover transition-transform group-hover:scale-105"><span class="block truncate px-2 py-2 text-xs text-[#66746c]">{{ $evidence['file_name'] }}</span></a>@endforeach</div>@endif</div></div>
                @endif
            </x-card>
        @endif
    @endif

    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wide text-[#8a9990]">Riwayat Permohonan</h3>
    <x-card>
        @if (count($timeline) === 0)
            <div class="p-5 text-sm text-[#8a9990]">Belum ada aktivitas permohonan.</div>
        @else
            <ol class="px-5 py-5">
                @foreach ($timeline as $i => $item)
                    @php $isLast = $i === array_key_last($timeline); $actorText = trim(($item['actor'] ?? '').($item['actor_role'] ? ' ('.$roleLabel($item['actor_role']).')' : '')); @endphp
                    <li class="relative flex gap-4 pb-6 last:pb-0">@if (! $isLast)<span class="absolute left-[11px] top-6 h-full w-0.5 bg-[#e4ece7]"></span>@endif<span class="relative z-10 mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $isLast ? 'bg-[#176b45] text-white' : 'bg-[#e8f4ed] text-[#176b45]' }}">{{ $isLast ? '•' : '✓' }}</span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold text-[#173b29]">{{ $item['label'] }}</p><p class="text-xs text-[#8a9990]">{{ $formatDate($item['waktu']) }}</p></div>@if ($actorText !== '')<p class="mt-0.5 text-xs text-[#8a9990]">oleh {{ $actorText }}</p>@endif @if ($item['catatan'])<p class="mt-2 whitespace-pre-line rounded-lg bg-[#fafcfb] p-2.5 text-xs leading-relaxed text-[#66746c]">{{ $item['catatan'] }}</p>@endif</div></li>
                @endforeach
            </ol>
        @endif
    </x-card>

    <div class="mt-6 flex flex-wrap items-center gap-3"><a href="{{ route('permohonan.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#dbe5df] bg-white px-5 py-2.5 text-sm font-semibold text-[#66746c] hover:bg-[#f3f8f4]">← Kembali ke Daftar</a></div>
@endsection
