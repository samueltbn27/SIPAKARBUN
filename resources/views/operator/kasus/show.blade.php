@extends('layouts.app')

@section('title', 'Detail Kasus')

@php
    $statusLabels = [
        'diterima' => 'Diterima — Menunggu Penugasan',
        'ditugaskan' => 'Ditugaskan',
        'sedang_direview' => 'Sedang Direview',
        'ditunda' => 'Ditunda',
        'siap_dieksekusi' => 'Siap Dieksekusi',
        'dalam_pelaksanaan' => 'Dalam Pelaksanaan',
        'selesai' => 'Selesai',
    ];
    $extensionStatusLabels = [
        'pending' => 'Menunggu tinjauan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan karena penugasan diganti',
    ];
    $assignment = $kasus->penugasanAktif;
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('operator.kasus.index') }}" class="text-sm text-[#176b45]">← Monitoring kasus</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-[#173b29]">{{ $kasus->kasus_code }}</h1>
            <span class="rounded-full bg-[#eef6f1] px-3 py-1.5 text-sm font-semibold text-[#176b45]">
                {{ $statusLabels[$kasus->current_status] ?? $kasus->current_status }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Perubahan belum disimpan.</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-5 rounded-xl border border-[#e6eee8] bg-white p-5 sm:grid-cols-2">
        <div>
            <h2 class="font-bold text-[#173b29]">Ringkasan</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div><dt class="text-gray-500">Komoditas</dt><dd>{{ $kasus->komoditas_name_snapshot ?: '-' }}</dd></div>
                <div><dt class="text-gray-500">Penyakit</dt><dd>{{ $kasus->penyakit_name_snapshot ?: '-' }}</dd></div>
                <div><dt class="text-gray-500">Lokasi</dt><dd>{{ $kasus->latitude_kasus ?? '-' }}, {{ $kasus->longitude_kasus ?? '-' }}</dd></div>
            </dl>
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-[#173b29]">Penugasan POPT</h2>
                @if($monitoring['is_overdue'])
                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Melewati Batas Waktu</span>
                @elseif($assignment?->deadline_at)
                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Tepat Waktu</span>
                @endif
            </div>

            @if($assignment)
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="text-gray-500">POPT</dt><dd class="font-semibold text-[#173b29]">{{ $assignment->popt?->name ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Penugasan</dt><dd>{{ $assignment->assigned_at?->format('d-m-Y H:i') ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Target Penyelesaian</dt><dd>{{ $assignment->deadline_at?->format('d-m-Y H:i') ?? 'Target belum tersedia' }}</dd></div>
                    <div>
                        <dt class="text-gray-500">Status Penerimaan</dt>
                        <dd>{{ $assignment->accepted_at ? 'Penugasan diterima pada '.$assignment->accepted_at->format('d-m-Y H:i') : 'Menunggu POPT menerima penugasan' }}</dd>
                    </div>
                    <div><dt class="text-gray-500">Status Waktu</dt><dd>{{ $monitoring['is_overdue'] ? 'Melewati Batas Waktu' : ($assignment->deadline_at ? 'Tepat Waktu' : 'Target belum tersedia') }}</dd></div>
                </dl>
            @else
                <p class="mt-4 text-sm text-gray-500">Belum ditugaskan.</p>
            @endif

            @if($kasus->current_status !== 'selesai')
                <form method="POST" action="{{ route('operator.kasus.assign', $kasus->id) }}" class="mt-5 space-y-3 border-t border-[#e6eee8] pt-4">
                    @csrf
                    <div>
                        <label for="popt_id" class="mb-1 block text-sm font-medium text-gray-700">POPT</label>
                        <select id="popt_id" name="popt_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Pilih POPT aktif</option>
                            @foreach($popts as $popt)
                                <option value="{{ $popt->id }}" @selected(old('popt_id', $assignment?->popt_id) == $popt->id)>{{ $popt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="deadline_at" class="mb-1 block text-sm font-medium text-gray-700">Target Penyelesaian</label>
                        <input id="deadline_at" name="deadline_at" type="datetime-local" required value="{{ old('deadline_at', $assignment?->deadline_at?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-lg border-gray-300 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Wajib diisi dengan waktu yang masih akan datang.</p>
                    </div>
                    <div>
                        <label for="catatan" class="mb-1 block text-sm font-medium text-gray-700">Catatan Penugasan</label>
                        <textarea id="catatan" name="catatan" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Catatan untuk POPT (opsional)">{{ old('catatan') }}</textarea>
                    </div>
                    <button class="w-full rounded-lg bg-[#176b45] px-4 py-2 text-sm font-semibold text-white">Tugaskan POPT</button>
                </form>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-[#173b29]">Permintaan Perpanjangan</h2>
                <p class="mt-1 text-sm text-gray-500">Tinjau usulan deadline dari POPT. Riwayat keputusan tetap tersimpan.</p>
            </div>
        </div>

        <div class="mt-4 space-y-4">
            @forelse($kasus->extensionRequests as $extension)
                <article class="rounded-lg border border-[#e6eee8] p-4 text-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-[#173b29]">{{ $extension->requester?->name ?? 'POPT' }}</p>
                            <p class="text-xs text-gray-500">Dikirim {{ $extension->created_at?->format('d-m-Y H:i') ?? '-' }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $extensionStatusLabels[$extension->status] ?? $extension->status }}</span>
                    </div>
                    <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                        <div><dt class="text-gray-500">Deadline Saat Ini</dt><dd>{{ $extension->current_deadline_at?->format('d-m-Y H:i') ?? 'Belum tersedia' }}</dd></div>
                        <div><dt class="text-gray-500">Usulan Deadline Baru</dt><dd>{{ $extension->proposed_deadline_at?->format('d-m-Y H:i') ?? '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-gray-500">Alasan Perpanjangan</dt><dd>{{ $extension->reason }}</dd></div>
                        @if($extension->reviewed_at)
                            <div><dt class="text-gray-500">Ditinjau Oleh</dt><dd>{{ $extension->reviewer?->name ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500">Waktu Tinjauan</dt><dd>{{ $extension->reviewed_at->format('d-m-Y H:i') }}</dd></div>
                        @endif
                        @if($extension->review_note)
                            <div class="sm:col-span-2"><dt class="text-gray-500">Catatan Tinjauan</dt><dd>{{ $extension->review_note }}</dd></div>
                        @endif
                    </dl>

                    @if($extension->status === 'pending')
                        <div class="mt-4 grid gap-3 border-t border-[#e6eee8] pt-4 sm:grid-cols-2">
                            <form method="POST" action="{{ route('operator.kasus.extension.approve', [$kasus->id, $extension->id]) }}" class="space-y-2">
                                @csrf
                                <label for="approve_note_{{ $extension->id }}" class="sr-only">Catatan Keputusan</label>
                                <input id="approve_note_{{ $extension->id }}" name="review_note" type="text" maxlength="2000" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Catatan Keputusan (opsional)">
                                <button class="w-full rounded-lg bg-[#176b45] px-4 py-2 text-sm font-semibold text-white">Setujui Perpanjangan</button>
                            </form>
                            <form method="POST" action="{{ route('operator.kasus.extension.reject', [$kasus->id, $extension->id]) }}" class="space-y-2">
                                @csrf
                                <label for="reject_note_{{ $extension->id }}" class="sr-only">Catatan Keputusan</label>
                                <input id="reject_note_{{ $extension->id }}" name="review_note" type="text" maxlength="2000" required class="w-full rounded-lg border-gray-300 text-sm" placeholder="Catatan Keputusan (wajib)">
                                <button class="w-full rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700">Tolak Perpanjangan</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-sm text-gray-500">Belum ada permintaan perpanjangan.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
        <h2 class="font-bold text-[#173b29]">Riwayat progres</h2>
        <ol class="mt-4 space-y-3">
            @forelse($kasus->riwayatStatus as $riwayat)
                <li class="border-l-2 border-[#cfe2d5] pl-3 text-sm">
                    <strong>{{ $statusLabels[$riwayat->status] ?? $riwayat->status }}</strong>
                    <span class="text-gray-500"> · {{ $riwayat->created_at?->format('d-m-Y H:i') }}</span>
                    <p class="text-gray-600">{{ $riwayat->catatan ?: 'Tanpa catatan' }}</p>
                </li>
            @empty
                <li class="text-sm text-gray-500">Belum ada riwayat.</li>
            @endforelse
        </ol>
    </section>
</div>
@endsection
