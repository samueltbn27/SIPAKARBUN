@extends('layouts.app')

@section('title', 'Detail Permohonan')

@php
    $diagnosis = $permohonan->diagnosis;
    $isPending = in_array($permohonan->status, ['diajukan', 'sedang_direview'], true);
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-center justify-between"><div><a href="{{ route('operator.permohonan') }}" class="text-sm text-[#176b45]">← Permohonan masuk</a><h1 class="mt-2 text-2xl font-bold text-[#173b29]">{{ $permohonan->permohonan_code }}</h1></div><x-kasus.handling-status-badge :status="$primaryStatus" /></div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-xl border border-[#e6eee8] bg-white p-5"><h2 class="font-bold text-[#173b29]">Pemohon dan lokasi</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-gray-500">Pemohon</dt><dd class="font-semibold">{{ $permohonan->creator?->name ?? '-' }}</dd></div><div><dt class="text-gray-500">Kelompok tani</dt><dd>{{ $permohonan->kelompok_tani_name_snapshot }}</dd></div><div><dt class="text-gray-500">Lokasi kasus</dt><dd>{{ $permohonan->alamat_kasus ?: '-' }}<br>{{ $permohonan->kabupaten ?: '-' }}, {{ $permohonan->kecamatan ?: '-' }}, {{ $permohonan->kelurahan ?: '-' }}</dd></div><div><dt class="text-gray-500">Koordinat</dt><dd>{{ $permohonan->latitude_kasus ?? '-' }}, {{ $permohonan->longitude_kasus ?? '-' }}</dd></div><div><dt class="text-gray-500">Konfirmasi lokasi</dt><dd>@if ($permohonan->lokasi_dikonfirmasi)<span class="font-semibold text-[#176b45]">✓ Dikonfirmasi pemohon</span>@if ($permohonan->lokasi_sama_dengan_poktan === true)<span class="text-gray-500"> — sama dengan titik referensi Poktan</span>@elseif ($permohonan->lokasi_sama_dengan_poktan === false)<span class="text-gray-500"> — disesuaikan dari titik referensi Poktan</span>@endif @else<span class="font-semibold text-amber-700">Belum ada konfirmasi (data lama)</span>@endif</dd></div><div><dt class="text-gray-500">Catatan pemohon</dt><dd>{{ $permohonan->catatan_pemohon ?: '-' }}</dd></div></dl></section>
        <section class="rounded-xl border border-[#e6eee8] bg-white p-5"><h2 class="font-bold text-[#173b29]">Hasil diagnosis</h2>@if($diagnosis)<p class="mt-3 text-sm text-gray-600">Komoditas: <strong>{{ $diagnosis->commodity_id }}</strong></p><ul class="mt-3 space-y-2 text-sm">@forelse($diagnosis->results as $result)<li class="rounded-lg bg-[#f7faf8] px-3 py-2"><strong>{{ $result->disease_name_snapshot }}</strong> <span class="text-gray-500">CF {{ $result->final_cf }}</span></li>@empty<li class="text-gray-500">Hasil diagnosis tidak tersedia.</li>@endforelse</ul>@else<p class="mt-3 text-sm text-gray-500">Diagnosis tidak tersedia.</p>@endif</section>
    </div>

    @if($primaryStatus['history_note'])
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="note">{{ $primaryStatus['history_note'] }}</p>
    @endif
    @if($isPending)
        <section class="rounded-xl border border-[#e6eee8] bg-white p-5">
            <h2 class="font-bold text-[#173b29]">Keputusan Operator</h2>
            <p class="mt-2 text-sm text-[#66746c]">Persetujuan sekaligus menetapkan POPT dan target penyelesaian. Kasus langsung masuk Dalam Penanganan; waktu mulai kerja dicatat oleh POPT.</p>
            @if($permohonan->status === 'diajukan')
                <p class="mt-2 text-sm text-[#66746c]">Mulai review terlebih dahulu sebelum memberikan keputusan.</p>
                <form method="POST" action="{{ route('operator.permohonan.review', $permohonan->id) }}" class="mt-4">
                    @csrf
                    <button class="min-h-11 w-full rounded-lg border border-[#176b45] px-4 py-2.5 text-sm font-semibold text-[#176b45] sm:w-auto">Mulai Review</button>
                </form>
            @endif
            <div class="mt-5 grid min-w-0 gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('operator.permohonan.accept', $permohonan->id) }}" class="min-w-0 space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <fieldset class="min-w-0 space-y-4" @disabled($permohonan->status !== 'sedang_direview')>
                        <legend class="text-sm font-semibold text-[#173b29]">Terima Permohonan</legend>
                        <div>
                            <label for="approval-popt" class="block text-sm font-medium text-gray-700">POPT Penanggung Jawab <span class="text-red-600">*</span></label>
                            <select id="approval-popt" name="popt_id" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300 text-sm" aria-describedby="approval-popt-help">
                                <option value="">Pilih POPT aktif</option>
                                @foreach($popts as $popt)
                                    <option value="{{ $popt->id }}" @selected((string) old('popt_id') === (string) $popt->id)>{{ $popt->name }}</option>
                                @endforeach
                            </select>
                            <p id="approval-popt-help" class="mt-1 text-xs text-gray-500">{{ $popts->isEmpty() ? 'Belum ada POPT aktif. Persetujuan belum dapat dilakukan.' : 'Petugas ini akan langsung menerima penugasan.' }}</p>
                            @error('popt_id')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="approval-deadline" class="block text-sm font-medium text-gray-700">Target Penyelesaian <span class="text-red-600">*</span></label>
                            <input id="approval-deadline" name="deadline_at" type="datetime-local" required min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" value="{{ old('deadline_at') }}" class="mt-1 min-h-11 w-full min-w-0 rounded-lg border-gray-300 text-sm">
                            <p class="mt-1 text-xs text-gray-500">Pilih tanggal dan waktu di masa depan.</p>
                            @error('deadline_at')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="approval-note" class="block text-sm font-medium text-gray-700">Catatan Penerimaan <span class="font-normal text-gray-500">(opsional)</span></label>
                            <textarea id="approval-note" name="catatan" rows="3" maxlength="2000" class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('popt_id') ? old('catatan') : '' }}</textarea>
                        </div>
                        <button type="submit" :disabled="submitting" @disabled($popts->isEmpty()) class="min-h-11 w-full rounded-lg bg-action-primary px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50" x-text="submitting ? 'Menyimpan persetujuan…' : 'Terima dan Tugaskan POPT'">Terima dan Tugaskan POPT</button>
                    </fieldset>
                </form>
                <form method="POST" action="{{ route('operator.permohonan.reject', $permohonan->id) }}" class="min-w-0 space-y-4">
                    @csrf
                    <fieldset class="min-w-0 space-y-4" @disabled($permohonan->status !== 'sedang_direview')>
                        <legend class="text-sm font-semibold text-[#173b29]">Tolak Permohonan</legend>
                        <div>
                            <label for="rejection-reason" class="block text-sm font-medium text-gray-700">Alasan Penolakan <span class="text-red-600">*</span></label>
                            <textarea id="rejection-reason" name="catatan" rows="5" required maxlength="2000" class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('popt_id') ? '' : old('catatan') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Alasan akan ditampilkan kepada Poktan.</p>
                        </div>
                        <button class="min-h-11 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Tolak Permohonan</button>
                    </fieldset>
                </form>
            </div>
        </section>
    @elseif($permohonan->kasus)
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-900">Permohonan diterima menjadi kasus <strong>{{ $permohonan->kasus->kasus_code }}</strong>. <a class="font-bold underline" href="{{ route('operator.kasus.show', $permohonan->kasus->id) }}">Buka detail kasus →</a></div>
    @endif
</div>
@endsection
