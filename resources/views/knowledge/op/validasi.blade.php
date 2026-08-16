@extends('layouts.app')

@section('title', 'Validasi Pengajuan')
@section('subtitle', 'Periksa dan putuskan pengajuan kasus')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Pengajuan Kasus</span><span>/</span><span class="text-[#176b45]">Validasi Pengajuan</span></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Validasi Pengajuan</h1>
        <p class="mt-1 text-sm text-[#77847c]">Periksa detail pengajuan, lalu terima (diteruskan ke POPT) atau tolak dengan alasan.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7 text-center">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#e8f4ed] text-[#176b45] mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <h2 class="text-base font-bold text-[#173b29]">Diterima</h2>
            <p class="mt-1 text-xs text-[#8b9790]">Pengajuan diteruskan ke POPT untuk pemeriksaan lapangan, diagnosis, dan tindak lanjut.</p>
            <span class="inline-flex mt-4 rounded-full bg-[#e8f4ed] px-3 py-1 text-xs font-semibold text-[#176b45]">0 pengajuan</span>
        </section>

        <section class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7 text-center">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#fdeaea] text-[#c53030] mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <h2 class="text-base font-bold text-[#173b29]">Ditolak</h2>
            <p class="mt-1 text-xs text-[#8b9790]">Pengajuan ditolak dengan alasan yang dikembalikan ke Poktan/Gapoktan.</p>
            <span class="inline-flex mt-4 rounded-full bg-[#fdeaea] px-3 py-1 text-xs font-semibold text-[#c53030]">0 pengajuan</span>
        </section>
    </div>

    <div class="rounded-xl bg-white border border-[#e6eee8] px-5 py-4 flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#fff4df] text-[#b8860b] flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </span>
        <p class="text-xs text-[#77847c]">Fitur validasi aktif setelah modul <strong>Diagnosis &amp; Kasus (Mahasiswa 2)</strong> terintegrasi.</p>
    </div>
</div>
@endsection
