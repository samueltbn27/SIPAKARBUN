@extends('layouts.app')

@section('title', 'Detail Gejala')
@section('subtitle', 'Definisi operasional dan referensi observasi gejala.')

@section('content')
@php($canEditRecord = (auth()->user()?->hasAnyRole(['admin', 'operator_uptd']) ?? false) || (auth()->user()?->hasRole('popt') && $gejala->status === 'draft'))
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $gejala->kode ?: 'Kode belum tersedia' }}</p>
                <h2 class="mt-1 text-xl font-bold text-[#173b29]">{{ $gejala->nama }}</h2>
            </div>
            <x-knowledge.status-badge :status="$gejala->status" />
        </div>
        @if ($gejala->image_path)
            <figure class="mt-5">
                <img src="{{ \App\Support\PublicStorageUrl::make($gejala->image_path) }}" alt="Foto gejala {{ $gejala->nama }}" class="max-h-96 w-full rounded-xl border border-gray-200 bg-gray-50 object-contain">
            </figure>
        @endif
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Deskripsi</dt><dd class="mt-1 whitespace-pre-line text-sm text-gray-800">{{ $gejala->deskripsi ?: 'Belum tersedia' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kriteria Observasi</dt><dd class="mt-1 whitespace-pre-line break-words text-sm text-gray-800">{{ $gejala->kriteria_observasi ?: 'Belum tersedia' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Metode Pengamatan</dt><dd class="mt-1 text-sm text-gray-800">{{ $gejala->metode_pengamatan ?: 'Belum tersedia' }}</dd></div>
        </dl>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-bold text-[#173b29]">Referensi Gejala</h2>
        @if ($gejala->referensi_judul || $gejala->referensi_penulis || $gejala->referensi_tahun || $gejala->referensi_url)
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Jenis</dt><dd class="mt-1 text-sm text-gray-800">{{ $gejala->referensiJenisLabel() }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Judul</dt><dd class="mt-1 break-words text-sm text-gray-800">{{ $gejala->referensi_judul ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penulis</dt><dd class="mt-1 text-sm text-gray-800">{{ $gejala->referensi_penulis ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tahun</dt><dd class="mt-1 text-sm text-gray-800">{{ $gejala->referensi_tahun ?: 'Belum tersedia' }}</dd></div>
                @if ($gejala->referensi_url)<div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">DOI / URL</dt><dd class="mt-1 break-all text-sm"><a class="text-green-700 underline" href="{{ $gejala->referensi_url }}" target="_blank" rel="noopener noreferrer">Lihat Referensi</a></dd></div>@endif
            </dl>
        @else
            <p class="mt-3 text-sm text-gray-500">Referensi belum tersedia.</p>
        @endif
    </section>

    <div class="flex flex-wrap gap-3">
        @if ($canEditRecord)<a href="{{ route('knowledge.gejala.edit', $gejala) }}" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Edit Gejala</a>@endif
        <a href="{{ route('knowledge.gejala.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Kembali</a>
    </div>
</div>
@endsection
