@extends('layouts.app')

@section('title', 'Detail Metode CF')
@section('subtitle', 'Definisi skala, pertanyaan elicitation, dan referensi metodologi.')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-xl border border-[#d6ebe0] bg-[#f7fcf9] p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-[#66746c]">Metode CF</p><h2 class="mt-1 text-xl font-bold text-[#173b29]">{{ $cfMethod->name }}</h2><p class="mt-1 text-sm text-[#66746c]">Versi {{ $cfMethod->version }} · {{ $cfMethod->is_active ? 'Aktif' : 'Nonaktif' }}</p></div>@if(auth()->user()?->hasRole('admin'))<a href="{{ route('knowledge.cf-methods.edit', $cfMethod) }}" class="rounded-lg bg-[#176b45] px-3 py-2 text-sm font-semibold text-white">Edit</a>@endif</div>
        <p class="mt-5 whitespace-pre-line text-sm leading-6 text-gray-800">{{ $cfMethod->description ?: 'Belum tersedia' }}</p>
    </section>
    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-bold text-[#173b29]">Pertanyaan Elicitation</h2>
        <p class="mt-2 text-sm leading-6 text-gray-800">{{ $cfMethod->elicitation_question_template ?: 'Belum tersedia' }}</p>
        <h2 class="mt-6 text-base font-bold text-[#173b29]">Skala Elicitation yang Digunakan SIPAKARBUN</h2>
        <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Penilaian</th><th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">CF</th></tr></thead><tbody class="divide-y divide-gray-200">@foreach($cfMethod->scaleOptions() as $option)<tr><td class="px-4 py-2 text-sm text-gray-800">{{ $option['term'] }}</td><td class="px-4 py-2 font-mono text-sm text-gray-800">{{ number_format($option['cf'], 3) }}</td></tr>@endforeach</tbody></table></div>
        <p class="mt-3 text-xs text-gray-500">Skala ini adalah konfigurasi metodologi yang diadopsi untuk SIPAKARBUN, bukan klaim bahwa ia satu-satunya standar universal CF.</p>
    </section>
    <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-bold text-[#173b29]">Referensi Metodologi CF</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Judul</dt><dd class="mt-1 text-sm text-gray-800">{{ $cfMethod->reference_title ?: 'Belum tersedia' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penulis</dt><dd class="mt-1 text-sm text-gray-800">{{ $cfMethod->reference_authors ?: 'Belum tersedia' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tahun</dt><dd class="mt-1 text-sm text-gray-800">{{ $cfMethod->reference_year ?: 'Belum tersedia' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">DOI / URL</dt><dd class="mt-1 break-all text-sm text-gray-800">@if($cfMethod->reference_doi || $cfMethod->reference_url){{ $cfMethod->reference_doi ?: $cfMethod->reference_url }}@else Belum tersedia — memerlukan referensi terverifikasi @endif</dd></div></dl>
    </section>
    <a href="{{ route('knowledge.cf-methods.index') }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Kembali</a>
</div>
@endsection
