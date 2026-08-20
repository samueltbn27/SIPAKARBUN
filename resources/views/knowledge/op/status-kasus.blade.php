@extends('layouts.app')

@section('title', 'Status Kasus')
@section('subtitle', 'Monitoring penanganan kasus')

@section('content')
<div class="max-w-[1500px] mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8c9890] mb-2"><span>Monitoring</span><span>/</span><span class="text-[#176b45]">Status Kasus</span></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#173b29]">Status Kasus</h1>
        <p class="mt-1 text-sm text-[#77847c]">Pantau perkembangan kasus yang telah diteruskan ke POPT: pemeriksaan, diagnosis, dan tindak lanjut.</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php($stages = [
            ['label' => 'Diteruskan ke POPT', 'color' => '#176b45', 'bg' => '#e8f4ed'],
            ['label' => 'Pemeriksaan Lapangan', 'color' => '#b8860b', 'bg' => '#fff4df'],
            ['label' => 'Diagnosis', 'color' => '#3d6b78', 'bg' => '#eef5f7'],
            ['label' => 'Tindak Lanjut', 'color' => '#7c3aed', 'bg' => '#f2edf8'],
        ])
        @foreach($stages as $stage)
        <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-5 text-center">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl mb-3" style="background:{{ $stage['bg'] }};">
                <svg class="w-5 h-5" style="color:{{ $stage['color'] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </span>
            <div class="text-2xl font-bold text-[#173b29]">0</div>
            <div class="text-xs font-medium text-[#748179] mt-1">{{ $stage['label'] }}</div>
        </div>
        @endforeach
    </div>

    <div class="soft-card rounded-xl border border-[#e6eee8] bg-white p-6 sm:p-7">
        <div class="text-center py-10">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#f3f8f4] text-[#b0c4ba] mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 3L9 7m0 0l6-3"/></svg>
            </span>
            <p class="text-sm font-medium text-[#8b9790]">Belum ada kasus untuk dimonitoring.</p>
            <p class="text-xs text-[#b0bab3] mt-1">Monitoring akan aktif setelah modul Diagnosis &amp; Kasus (Mahasiswa 2) terintegrasi.</p>
        </div>
    </div>
</div>
@endsection
