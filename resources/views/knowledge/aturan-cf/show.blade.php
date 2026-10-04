@extends('layouts.app')

@section('title', 'Detail Aturan Penyakit')
@section('subtitle', 'Hubungan penyakit dan gejala pendukung berdasarkan penilaian pakar.')

@section('content')
@php
    $canManageKnowledge = auth()->user()?->hasAnyRole(['admin', 'operator_uptd']) ?? false;
    $canEditRecord = $canManageKnowledge || (auth()->user()?->hasRole('popt') && $aturanCf->status === 'draft');
@endphp
<div class="mx-auto max-w-4xl space-y-6">
    @if($aturanCf->jenis_sumber === \App\Models\AturanCf::SOURCE_SIMULATION)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="font-semibold">Data Simulasi.</span> Nilai ini digunakan untuk pengujian sistem dan belum divalidasi pakar.
        </div>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Relasi Aturan</p>
                <h2 class="mt-1 text-xl font-bold text-[#173b29]">{{ $aturanCf->penyakit?->nama ?? 'Penyakit tidak tersedia' }}</h2>
                <p class="mt-1 text-sm text-gray-600">Gejala: {{ $aturanCf->gejala?->nama ?? 'Tidak tersedia' }}</p>
            </div>
            <div class="flex flex-wrap gap-2"><x-knowledge.status-badge :status="$aturanCf->status" /><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $aturanCf->statusValidasiLabel() }}</span></div>
        </div>
        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nilai CF Pakar</dt><dd class="mt-1 font-mono text-xl font-bold text-[#176b45]">{{ number_format((float) $aturanCf->cf_pakar, 3) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Jenis Sumber</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ $aturanCf->jenisSumberLabel() }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Metode Penentuan</dt><dd class="mt-1 break-words text-sm text-gray-800">{{ $aturanCf->pendekatan ?: 'Belum tersedia' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dasar Penentuan</dt><dd class="mt-1 whitespace-pre-line break-words text-sm text-gray-800">{{ $aturanCf->dasar_penentuan ?: 'Belum tersedia' }}</dd></div>
        </dl>
    </section>

    <section class="rounded-xl border border-[#d6ebe0] bg-[#f7fcf9] p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Metode Penentuan CF</p>
                <h2 class="mt-1 text-lg font-bold text-[#173b29]">{{ $aturanCf->cfMethod?->name ?? 'Legacy / Belum tercatat' }}</h2>
                @if($aturanCf->cfMethod)<p class="mt-1 text-sm text-[#66746c]">Versi {{ $aturanCf->cfMethod->version }} · {{ $aturanCf->cfMethod->description }}</p>@endif
            </div>
            @if($aturanCf->cfMethod)<a href="{{ route('knowledge.cf-methods.show', $aturanCf->cfMethod) }}" class="text-sm font-semibold text-[#176b45] hover:underline">Lihat Pedoman CF Lengkap</a>@endif
        </div>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Pertanyaan Elicitation</dt><dd class="mt-1 text-sm leading-6 text-gray-800">{{ $aturanCf->cfMethod?->elicitation_question_template ? str_replace(['{gejala}', '{penyakit}'], [$aturanCf->gejala?->nama ?? '[gejala]', $aturanCf->penyakit?->nama ?? '[penyakit]'], $aturanCf->cfMethod->elicitation_question_template) : 'Belum tersedia' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Referensi Metodologi CF</dt><dd class="mt-1 break-words text-sm text-gray-800">@if($aturanCf->cfMethod?->reference_url)<a href="{{ $aturanCf->cfMethod->reference_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-green-700 underline">{{ $aturanCf->cfMethod->referenceLabel() }}</a>@else{{ $aturanCf->cfMethod?->referenceLabel() && $aturanCf->cfMethod->referenceLabel() !== 'Belum tersedia' ? $aturanCf->cfMethod->referenceLabel() : 'Referensi metodologi belum tersedia.' }}@endif</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Skala</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->cfMethod ? 'Tersimpan sebagai skala metode' : 'Belum tersedia' }}</dd></div>
        </dl>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kekuatan Hubungan</p>
        <dl class="mt-4 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kekuatan Hubungan</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ $aturanCf->expert_term ?: 'Belum tercatat' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nilai CF</dt><dd class="mt-1 font-mono text-xl font-bold text-[#176b45]">{{ number_format((float) $aturanCf->cf_pakar, 3) }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Alasan / Rationale</dt><dd class="mt-1 whitespace-pre-line break-words text-sm text-gray-800">{{ $aturanCf->expert_rationale ?: 'Belum tersedia' }}</dd></div>
        </dl>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Provenance Penilaian</p>
        <dl class="mt-4 grid gap-5 sm:grid-cols-3">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pakar Penilai</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->expert_name ?: 'Belum tersedia' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Instansi</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->expert_institution ?: 'Belum tersedia' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tanggal Elicitation</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->elicited_at?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
        </dl>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">Referensi</h2>
            <dl class="mt-4 space-y-4">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Judul</dt><dd class="mt-1 break-words text-sm text-gray-800">{{ $aturanCf->sumber ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penulis</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->referensi_penulis ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tahun</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->referensi_tahun ?: 'Belum tersedia' }}</dd></div>
                @if($aturanCf->referensi_url)<div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">DOI / URL</dt><dd class="mt-1 break-all text-sm"><a class="text-green-700 underline" href="{{ $aturanCf->referensi_url }}" target="_blank" rel="noopener noreferrer">Lihat Referensi</a></dd></div>@endif
            </dl>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-base font-bold text-[#173b29]">Validasi</h2>
            <dl class="mt-4 space-y-4">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ $aturanCf->statusValidasiLabel() }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Validator</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->validator_nama ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Instansi</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->validator_instansi ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tanggal</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->tanggal_validasi?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Terakhir Diubah</dt><dd class="mt-1 text-sm text-gray-800">{{ $aturanCf->updated_at?->format('d M Y H:i') ?? 'Belum tersedia' }}</dd></div>
            </dl>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        @if($canEditRecord)<a href="{{ route('knowledge.aturan-cf.edit', $aturanCf) }}" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Edit Aturan</a>@endif
        <a href="{{ route('knowledge.aturan-cf.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Kembali</a>
    </div>
</div>
@endsection
