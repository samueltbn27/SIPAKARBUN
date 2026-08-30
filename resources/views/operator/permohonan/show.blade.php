@extends('layouts.app')

@section('title', 'Detail Permohonan')

@php
    $statusLabels = ['diajukan' => 'Diajukan', 'sedang_direview' => 'Sedang Direview', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak'];
    $diagnosis = $permohonan->diagnosis;
    $isPending = in_array($permohonan->status, ['diajukan', 'sedang_direview'], true);
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-center justify-between"><div><a href="{{ route('operator.permohonan') }}" class="text-sm text-[#176b45]">← Permohonan masuk</a><h1 class="mt-2 text-2xl font-bold text-[#173b29]">{{ $permohonan->permohonan_code }}</h1></div><span class="rounded-full bg-[#eef6f1] px-3 py-1.5 text-sm font-semibold text-[#176b45]">{{ $statusLabels[$permohonan->status] ?? $permohonan->status }}</span></div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-xl border border-[#e6eee8] bg-white p-5"><h2 class="font-bold text-[#173b29]">Pemohon dan lokasi</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-gray-500">Pemohon</dt><dd class="font-semibold">{{ $permohonan->creator?->name ?? '-' }}</dd></div><div><dt class="text-gray-500">Kelompok tani</dt><dd>{{ $permohonan->kelompok_tani_name_snapshot }}</dd></div><div><dt class="text-gray-500">Lokasi kasus</dt><dd>{{ $permohonan->alamat_kasus ?: '-' }}<br>{{ $permohonan->kabupaten ?: '-' }}, {{ $permohonan->kecamatan ?: '-' }}, {{ $permohonan->kelurahan ?: '-' }}</dd></div><div><dt class="text-gray-500">Koordinat</dt><dd>{{ $permohonan->latitude_kasus ?? '-' }}, {{ $permohonan->longitude_kasus ?? '-' }}</dd></div><div><dt class="text-gray-500">Catatan pemohon</dt><dd>{{ $permohonan->catatan_pemohon ?: '-' }}</dd></div></dl></section>
        <section class="rounded-xl border border-[#e6eee8] bg-white p-5"><h2 class="font-bold text-[#173b29]">Hasil diagnosis</h2>@if($diagnosis)<p class="mt-3 text-sm text-gray-600">Komoditas: <strong>{{ $diagnosis->commodity_id }}</strong></p><ul class="mt-3 space-y-2 text-sm">@forelse($diagnosis->results as $result)<li class="rounded-lg bg-[#f7faf8] px-3 py-2"><strong>{{ $result->disease_name_snapshot }}</strong> <span class="text-gray-500">CF {{ $result->final_cf }}</span></li>@empty<li class="text-gray-500">Hasil diagnosis tidak tersedia.</li>@endforelse</ul>@else<p class="mt-3 text-sm text-gray-500">Diagnosis tidak tersedia.</p>@endif</section>
    </div>

    @if($isPending)
    <section class="rounded-xl border border-[#e6eee8] bg-white p-5"><h2 class="font-bold text-[#173b29]">Keputusan Operator</h2><div class="mt-4 grid gap-4 lg:grid-cols-3"><form method="POST" action="{{ route('operator.permohonan.review', $permohonan->id) }}">@csrf<button class="w-full rounded-lg border border-[#176b45] px-4 py-2.5 text-sm font-semibold text-[#176b45]" @disabled($permohonan->status !== 'diajukan')>Mulai Review</button></form><form method="POST" action="{{ route('operator.permohonan.accept', $permohonan->id) }}" class="space-y-2">@csrf<textarea name="catatan" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Catatan penerimaan (opsional)"></textarea><button class="w-full rounded-lg bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white">Terima dan Buat Kasus</button></form><form method="POST" action="{{ route('operator.permohonan.reject', $permohonan->id) }}" class="space-y-2">@csrf<textarea name="catatan" rows="2" required class="w-full rounded-lg border-gray-300 text-sm" placeholder="Alasan penolakan (wajib)"></textarea><button class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white">Tolak Permohonan</button></form></div></section>
    @elseif($permohonan->kasus)
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-900">Permohonan diterima menjadi kasus <strong>{{ $permohonan->kasus->kasus_code }}</strong>. <a class="font-bold underline" href="{{ route('operator.kasus.show', $permohonan->kasus->id) }}">Buka detail kasus →</a></div>
    @endif
</div>
@endsection
